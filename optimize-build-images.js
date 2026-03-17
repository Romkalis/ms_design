#!/usr/bin/env node

const fs = require("fs");
const path = require("path");

function parseArgs(argv) {
	const args = {
		dir: "build/img",
		maxSide: 1600,
		maxBytes: 120 * 1024,
		dryRun: false,
		concurrency: 4,
		verbose: false,
	};

	for (let i = 0; i < argv.length; i += 1) {
		const a = argv[i];
		if (a === "--dir") args.dir = argv[i + 1];
		if (a === "--max-side") args.maxSide = Number(argv[i + 1]);
		if (a === "--max-kb") args.maxBytes = Number(argv[i + 1]) * 1024;
		if (a === "--max-bytes") args.maxBytes = Number(argv[i + 1]);
		if (a === "--dry-run") args.dryRun = true;
		if (a === "--concurrency") args.concurrency = Math.max(1, Number(argv[i + 1]) || 1);
		if (a === "--verbose") args.verbose = true;
		if (a === "--help" || a === "-h") args.help = true;
	}

	return args;
}

function formatBytes(bytes) {
	if (bytes < 1024) return `${bytes} B`;
	const kb = bytes / 1024;
	if (kb < 1024) return `${kb.toFixed(1)} KB`;
	const mb = kb / 1024;
	return `${mb.toFixed(1)} MB`;
}

async function ensureSharp() {
	try {
		return require("sharp");
	} catch (e) {
		process.stderr.write('Не найдена зависимость "sharp". Установи: npm i -D sharp\n');
		process.exitCode = 1;
		return null;
	}
}

async function walkFiles(dirAbs) {
	const out = [];
	const queue = [dirAbs];

	while (queue.length) {
		const current = queue.pop();
		let entries;
		try {
			entries = await fs.promises.readdir(current, {withFileTypes: true});
		} catch {
			continue;
		}
		for (const ent of entries) {
			const p = path.join(current, ent.name);
			if (ent.isDirectory()) {
				queue.push(p);
				continue;
			}
			if (!ent.isFile()) continue;
			out.push(p);
		}
	}

	return out;
}

function isRasterPath(filePath) {
	const ext = path.extname(filePath).toLowerCase();
	if (ext === ".svg") return false;
	return [".jpg", ".jpeg", ".png", ".webp", ".tif", ".tiff", ".avif", ".gif"].includes(ext);
}

function makeLimiter(max) {
	let running = 0;
	const queue = [];

	const runNext = () => {
		if (running >= max) return;
		const item = queue.shift();
		if (!item) return;
		running += 1;
		Promise.resolve()
			.then(item.fn)
			.then(item.resolve, item.reject)
			.finally(() => {
				running -= 1;
				runNext();
			});
	};

	return (fn) =>
		new Promise((resolve, reject) => {
			queue.push({fn, resolve, reject});
			runNext();
		});
}

async function statSafe(p) {
	try {
		return await fs.promises.stat(p);
	} catch {
		return null;
	}
}

async function optimizeOne({sharp, filePathAbs, rootAbs, maxSide, maxBytes, dryRun, verbose}) {
	const rel = path.relative(rootAbs, filePathAbs);
	const ext = path.extname(filePathAbs).toLowerCase();
	const inputStat = await statSafe(filePathAbs);
	if (!inputStat) return {kind: "skip", rel, reason: "missing"};

	let meta;
	try {
		meta = await sharp(filePathAbs, {failOnError: false}).metadata();
	} catch {
		return {kind: "skip", rel, reason: "unreadable"};
	}

	if (!meta || !meta.width || !meta.height) return {kind: "skip", rel, reason: "no-dimensions"};
	const longSide = Math.max(meta.width, meta.height);

	if (meta.format === "gif" && meta.pages && meta.pages > 1) return {kind: "skip", rel, reason: "animated-gif"};

	const isWebp = meta.format === "webp" || ext === ".webp";
	const needsResize = longSide > maxSide;
	const needsReencode = !isWebp || inputStat.size > maxBytes || needsResize;

	if (!needsReencode) return {kind: "ok", rel, before: inputStat.size, after: inputStat.size};

	const dirAbs = path.dirname(filePathAbs);
	const baseName = path.basename(filePathAbs, ext);
	const outAbs = path.join(dirAbs, `${baseName}.webp`);
	const outRel = path.relative(rootAbs, outAbs);

	let targetLong = Math.min(longSide, maxSide);
	let targetWidth = meta.width;
	let targetHeight = meta.height;
	if (longSide !== 0) {
		const ratio = targetLong / longSide;
		if (needsResize) {
			targetWidth = Math.max(1, Math.round(meta.width * ratio));
			targetHeight = Math.max(1, Math.round(meta.height * ratio));
		}
	}

	let best = null;
	let bestBytes = Infinity;
	let bestQuality = null;
	let bestW = targetWidth;
	let bestH = targetHeight;

	const qualities = [];
	for (let q = 82; q >= 35; q -= 7) qualities.push(q);
	if (!qualities.includes(35)) qualities.push(35);

	let scaledLong = Math.max(targetWidth, targetHeight);
	let scaledW = targetWidth;
	let scaledH = targetHeight;

	for (let scaleStep = 0; scaleStep < 10; scaleStep += 1) {
		for (const quality of qualities) {
			let pipeline = sharp(filePathAbs, {failOnError: false});
			pipeline = pipeline.resize({
				width: scaledW,
				height: scaledH,
				fit: "inside",
				withoutEnlargement: true,
			});

			let buf;
			try {
				buf = await pipeline.webp({quality, effort: 6}).toBuffer();
			} catch {
				continue;
			}

			if (buf.length < bestBytes) {
				best = buf;
				bestBytes = buf.length;
				bestQuality = quality;
				bestW = scaledW;
				bestH = scaledH;
			}

			if (buf.length <= maxBytes) break;
		}

		if (bestBytes <= maxBytes) break;
		if (scaledLong <= 320) break;

		scaledW = Math.max(1, Math.round(scaledW * 0.9));
		scaledH = Math.max(1, Math.round(scaledH * 0.9));
		scaledLong = Math.max(scaledW, scaledH);
	}

	if (!best) return {kind: "error", rel, reason: "encode-failed"};

	if (verbose) {
		const note = [];
		if (needsResize) note.push(`resize ${meta.width}x${meta.height} -> ${bestW}x${bestH}`);
		if (!isWebp) note.push("to webp");
		if (inputStat.size > maxBytes || bestBytes > maxBytes) note.push(`q=${bestQuality}`);
		process.stdout.write(`${rel} -> ${outRel} | ${note.join(", ")} | ${formatBytes(inputStat.size)} -> ${formatBytes(bestBytes)}\n`);
	}

	if (dryRun) return {kind: "changed", rel, outRel, before: inputStat.size, after: bestBytes, removedInput: !isWebp};

	const tmpAbs = path.join(dirAbs, `.${baseName}.${process.pid}.${Date.now()}.tmp.webp`);
	await fs.promises.writeFile(tmpAbs, best);
	await fs.promises.rename(tmpAbs, outAbs);

	if (!isWebp) {
		try {
			await fs.promises.unlink(filePathAbs);
		} catch {}
	}

	return {kind: "changed", rel, outRel, before: inputStat.size, after: bestBytes, removedInput: !isWebp};
}

async function main() {
	const args = parseArgs(process.argv.slice(2));
	if (args.help) {
		process.stdout.write(
			[
				"Оптимизация build/img: webp, max long side 1600, max size 120KB",
				"",
				"Запуск:",
				"  node optimize-build-images.js",
				"  node optimize-build-images.js --dir build/img",
				"  node optimize-build-images.js --max-side 1600 --max-kb 120",
				"  node optimize-build-images.js --dry-run --verbose",
				"",
			].join("\n")
		);
		return;
	}

	const sharp = await ensureSharp();
	if (!sharp) return;

	const rootAbs = path.resolve(process.cwd(), args.dir);
	const files = (await walkFiles(rootAbs)).filter(isRasterPath);

	const limit = makeLimiter(args.concurrency);
	const startedAt = Date.now();

	const summary = {
		total: files.length,
		ok: 0,
		changed: 0,
		skipped: 0,
		errors: 0,
		beforeBytes: 0,
		afterBytes: 0,
	};

	await Promise.all(
		files.map((filePathAbs) =>
			limit(async () => {
				const res = await optimizeOne({
					sharp,
					filePathAbs,
					rootAbs,
					maxSide: args.maxSide,
					maxBytes: args.maxBytes,
					dryRun: args.dryRun,
					verbose: args.verbose,
				});

				if (res.before) summary.beforeBytes += res.before;
				if (res.after) summary.afterBytes += res.after;

				if (res.kind === "ok") summary.ok += 1;
				else if (res.kind === "changed") summary.changed += 1;
				else if (res.kind === "skip") summary.skipped += 1;
				else summary.errors += 1;
			})
		)
	);

	const elapsedMs = Date.now() - startedAt;
	process.stdout.write(
		[
			"",
			`Файлов: ${summary.total}`,
			`Без изменений: ${summary.ok}`,
			`Оптимизировано: ${summary.changed}`,
			`Пропущено: ${summary.skipped}`,
			`Ошибок: ${summary.errors}`,
			`Размер: ${formatBytes(summary.beforeBytes)} -> ${formatBytes(summary.afterBytes)}`,
			`Время: ${(elapsedMs / 1000).toFixed(1)}s`,
			args.dryRun ? "Режим: dry-run" : "Режим: write",
			"",
		].join("\n")
	);
}

main().catch((e) => {
	process.stderr.write(`${e && e.stack ? e.stack : String(e)}\n`);
	process.exitCode = 1;
});
