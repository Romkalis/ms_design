const gulp = require("gulp");
const fileInclude = require("gulp-file-include");
const plumber = require("gulp-plumber");
const notify = require("gulp-notify");

// Тема собирается в docs/wp-content/themes/msk-design: заливается вместе со статикой в корень сайта
const THEME_DEST = "./docs/wp-content/themes/msk-design/";

const plumberNotify = (title) => {
	return {
		errorHandler: notify.onError({
			title: title,
			message: "Error <%= error.message %>",
			sound: false,
		}),
	};
};

// В php-шаблоны подставляются общие блоки шапки, подвала, формы
gulp.task("theme:php", function () {
	return gulp
		.src("./src/wp/theme/**/*.php")
		.pipe(plumber(plumberNotify("WP theme")))
		.pipe(fileInclude({prefix: "@@", basepath: "@file"}))
		.pipe(gulp.dest(THEME_DEST));
});

gulp.task("theme:assets", function () {
	return gulp.src(["./src/wp/theme/**/*", "!./src/wp/theme/**/*.php"], {nodir: true}).pipe(gulp.dest(THEME_DEST));
});

gulp.task("theme:docs", gulp.parallel("theme:php", "theme:assets"));

gulp.task("htaccess:wp", function () {
	return gulp.src("./src/wp/.htaccess").pipe(gulp.dest("./docs/"));
});
