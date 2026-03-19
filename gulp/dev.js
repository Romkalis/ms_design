const gulp = require("gulp");
const fileInclude = require("gulp-file-include");
const sass = require("gulp-sass")(require("sass"));
const sassGlob = require("gulp-sass-glob");
const server = require("gulp-server-livereload");
const clean = require("gulp-clean");
const fs = require("fs");
const sourceMaps = require("gulp-sourcemaps");
const plumber = require("gulp-plumber");
const notify = require("gulp-notify");
const webpack = require("webpack-stream");
const babel = require("gulp-babel");
const imagemin = require("gulp-imagemin");
const changed = require("gulp-changed");
const typograf = require("gulp-typograf");
const svgsprite = require("gulp-svg-sprite");
const replace = require("gulp-replace");
const webpHTML = require("gulp-webp-retina-html");
const imageminWebp = require("imagemin-webp");
const rename = require("gulp-rename");
const net = require("net");

function canListen(port, host) {
	return new Promise((resolve) => {
		const tester = net
			.createServer()
			.once("error", () => resolve(false))
			.once("listening", () => {
				tester.close(() => resolve(true));
			})
			.listen(port, host);
	});
}

function findFreePort(startPort, host) {
	return new Promise((resolve) => {
		const maxTries = 20;
		let port = startPort;

		const tryNext = () => {
			canListen(port, host).then((ok) => {
				if (ok) {
					resolve(port);
					return;
				}
				port += 1;
				if (port >= startPort + maxTries) {
					resolve(startPort);
					return;
				}
				tryNext();
			});
		};

		tryNext();
	});
}

gulp.task("clean:dev", function (done) {
	if (fs.existsSync("./build/")) {
		return gulp.src("./build/", {read: false}).pipe(clean({force: true}));
	}
	done();
});

const fileIncludeSetting = {
	prefix: "@@",
	basepath: "@file",
};

const plumberNotify = (title) => {
	return {
		errorHandler: notify.onError({
			title: title,
			message: "Error <%= error.message %>",
			sound: false,
		}),
	};
};

gulp.task("html:dev", function () {
	return gulp
		.src(["./src/html/**/*.html", "!./**/blocks/**/*.*", "!./src/html/docs/**/*.*"])
		.pipe(changed("./build/", {hasChanged: changed.compareContents}))
		.pipe(plumber(plumberNotify("HTML")))
		.pipe(fileInclude(fileIncludeSetting))
		.pipe(
			replace(/<img(?:.|\n|\r)*?>/g, function (match) {
				return match.replace(/\r?\n|\r/g, "").replace(/\s{2,}/g, " ");
			})
		) //удаляет лишние пробелы и переводы строк внутри тега <img>
		.pipe(replace(/(?<=src=|href=|srcset=)(['"])(\.(\.)?\/)*(img|images|fonts|css|scss|sass|js|files|audio|video)(\/[^\/'"]+(\/))?([^'"]*)\1/gi, "$1./$4$5$7$1"))
		.pipe(
			typograf({
				locale: ["ru", "en-US"],
				htmlEntity: {type: "digit"},
				safeTags: [
					["<\\?php", "\\?>"],
					["<no-typography>", "</no-typography>"],
				],
			})
		)
		.pipe(gulp.dest("./build/"));
});

gulp.task("sass:dev", function () {
	return gulp
		.src("./src/scss/**/*.scss")
		.pipe(changed("./build/css/"))
		.pipe(plumber(plumberNotify("SCSS")))
		.pipe(sourceMaps.init())
		.pipe(sassGlob())
		.pipe(sass())
		.pipe(replace(/(['"]?)(\.\.\/)+(img|images|fonts|css|scss|sass|js|files|audio|video)(\/[^\/'"]+(\/))?([^'"]*)\1/gi, "$1$2$3$4$6$1"))
		.pipe(sourceMaps.write())
		.pipe(gulp.dest("./build/css/"));
});

gulp.task("images:dev", function () {
	return (
		gulp
			.src(["./src/img/**/*.{jpg,jpeg,png}", "!./src/img/svgicons/**/*"])
			.pipe(
				imagemin([
					imageminWebp({
						quality: 85,
					}),
				])
			)
			.pipe(rename({extname: ".webp"}))
			.pipe(gulp.dest("./build/img/"))
			.pipe(gulp.src(["./src/img/**/*", "!./src/img/**/*.{jpg,jpeg,png}", "!./src/img/svgicons/**/*"]))
			.pipe(changed("./build/img/"))
			// .pipe(imagemin({ verbose: true }))
			.pipe(gulp.dest("./build/img/"))
	);
});

gulp.task("svg:dev", function () {
	return gulp.src("./src/img/svgicons/**/*").pipe(gulp.dest("./build/files/"));
});

gulp.task("files:dev", function () {
	return gulp.src("./src/files/**/*").pipe(changed("./build/files/")).pipe(gulp.dest("./build/files/"));
});

gulp.task("htaccess:dev", function () {
	return gulp.src("./src/.htaccess").pipe(changed("./build/")).pipe(gulp.dest("./build/"));
});

gulp.task("js:dev", function () {
	return (
		gulp
			.src("./src/js/*.js")
			.pipe(changed("./build/js/"))
			.pipe(plumber(plumberNotify("JS")))
			// .pipe(babel())
			.pipe(webpack(require("./../webpack.config.js")))
			.pipe(gulp.dest("./build/js/"))
	);
});

gulp.task("server:dev", function () {
	const host = process.env.DEV_HOST || "localhost";
	const preferredPort = Number(process.env.DEV_PORT) || 8000;
	const preferredLrPort = Number(process.env.LIVERELOAD_PORT) || 35729;

	return Promise.all([findFreePort(preferredPort, host), findFreePort(preferredLrPort, host)]).then(([port, livereloadPort]) => {
		const serverOptions = {
			host,
			port,
			livereload: {
				enable: true,
				port: livereloadPort,
				filter: function (filePath) {
					const normalized = String(filePath).replace(/\\/g, "/");
					if (normalized.includes("/node_modules/")) return false;
					if (normalized.includes("/fonts/")) return false;
					return !/\.(?:map|woff2?|ttf|otf|eot)(?:$|\?)/i.test(normalized);
				},
			},
			open: true,
		};

		return gulp.src("./build/").pipe(server(serverOptions));
	});
});

gulp.task("watch:dev", function () {
	gulp.watch("./src/scss/**/*.scss", gulp.parallel("sass:dev"));
	gulp.watch(["./src/html/**/*.html", "./src/html/**/*.json"], gulp.parallel("html:dev"));
	gulp.watch("./src/img/**/*", gulp.parallel("images:dev"));
	gulp.watch("./src/files/**/*", gulp.parallel("files:dev"));
	gulp.watch("./src/js/**/*.js", gulp.parallel("js:dev"));
	gulp.watch("./src/img/svgicons/*", gulp.series("svg:dev"));
});

gulp.task("favicon:dev", function () {
	return gulp.src("./src/img/favicons/**/*").pipe(changed("./build/img/favicons/")).pipe(gulp.dest("./build/img/favicons/"));
});

gulp.task("manifest:dev", function (done) {
	done();
});
