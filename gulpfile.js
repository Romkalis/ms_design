const gulp = require('gulp');

// Tasks
require('./gulp/dev.js');
require('./gulp/docs.js');
require('./gulp/fontsDev.js');
require('./gulp/fontsDocs.js');

gulp.task(
	'default',
	gulp.series(
		'clean:dev', 'fontsDev',
		gulp.parallel('html:dev', 'sass:dev', 'images:dev', 'svg:dev', 'files:dev', 'js:dev', 'favicon:dev', 'manifest:dev'),
		gulp.parallel('server:dev', 'watch:dev')
	)
);

gulp.task(
	'build',
	gulp.series(
		'clean:docs', 'fontsDocs',
		gulp.parallel('html:docs', 'sass:docs', 'images:docs', 'files:docs', 'js:docs', 'svg:dev', 'favicon:docs', 'manifest:docs'),
		gulp.parallel('server:docs')
	)
);
