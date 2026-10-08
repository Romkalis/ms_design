const gulp = require('gulp');

// Tasks
require('./gulp/dev.js');
require('./gulp/docs.js');
require('./gulp/fontsDev.js');
require('./gulp/fontsDocs.js');
require('./gulp/wp.js');

gulp.task(
	'default',
	gulp.series(
		'clean:dev', 'fontsDev',
		gulp.parallel('html:dev', 'sass:dev', 'images:dev', 'svg:dev', 'files:dev', 'htaccess:dev', 'seo:dev', 'js:dev', 'favicon:dev', 'manifest:dev'),
		gulp.parallel('server:dev', 'watch:dev')
	)
);

gulp.task(
	'build',
	gulp.series(
		'clean:docs', 'fontsDocs',
		gulp.parallel('html:docs', 'sass:docs', 'images:docs', 'files:docs', 'htaccess:docs', 'seo:docs', 'js:docs', 'svg:dev', 'favicon:docs', 'manifest:docs'),
		gulp.parallel('server:docs')
	)
);

// Статика + тема WordPress для галереи. Сервер не запускается: галерею без WordPress не посмотреть
gulp.task(
	'build:wp',
	gulp.series(
		function setWpTarget(done) {
			process.env.BUILD_TARGET = 'wp';
			done();
		},
		'clean:docs', 'fontsDocs',
		gulp.parallel('html:docs', 'sass:docs', 'images:docs', 'files:docs', 'htaccess:wp', 'seo:docs', 'js:docs', 'svg:dev', 'favicon:docs', 'manifest:docs', 'theme:docs')
	)
);
