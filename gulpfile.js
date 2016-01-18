
var fs = require('fs');
var path = require('path');

var gulp = require('gulp');
var plugins = require('gulp-load-plugins')(); // Load all gulp plugins automatically
plugins.jscs = require('gulp-jscs');
plugins.jshint = require('gulp-jshint');
plugins.replace = require('gulp-replace');

var runSequence = require('run-sequence');    // Temporary solution until gulp 4 (https://github.com/gulpjs/gulp/issues/355)

var phplint = require('phplint').lint;

var pkg = require('./package.json');
var dirs = pkg.configs.directories;

// ---------------------------------------------------------------------
// | Helper tasks                                                      |
// ---------------------------------------------------------------------

gulp.task('archive:create_archive_dir', function () {
	'use strict';
	fs.mkdirSync(path.resolve(dirs.archive), '0755');
});

gulp.task('archive:zip', function (done) {
	'use strict';

	var archiveName = path.resolve(dirs.archive, pkg.name + '_v' + pkg.version + '.zip');
	var archiver = require('archiver')('zip');
	var files = require('glob').sync('**/*.*', {
		'cwd': dirs.dist,
		'dot': true // include hidden files
	});
	var output = fs.createWriteStream(archiveName);

	/** @namespace archiver.on */
	archiver.on('error', function (error) {
		done();
		throw error;
	});

	output.on('close', done);

	files.forEach(function (file) {
		if (file.indexOf('.DS_Store') < 0) {
			var filePath = path.resolve(dirs.dist, file);

			// `archiver.bulk` does not maintain the file
			// permissions, so we need to add files individually
			archiver.append(fs.createReadStream(filePath), {
				'name': file,
				'mode': fs.statSync(filePath)
			});
		}
	});

	/** @namespace archiver.pipe */
	archiver.pipe(output);
	archiver.finalize();
});

gulp.task('clean', function (done) {
	'use strict';
	require('del')([
		dirs.archive,
		dirs.dist
	], done);
});

gulp.task('copy', [
	'copy:.htaccess',
	'copy:sql',
	'copy:index.html',
	'copy:license',
	'copy:misc',
	'copy:bootstrap',
	'copy:normalize'
]);

gulp.task('copy:.htaccess', function () {
	'use strict';
	return gulp.src('node_modules/apache-server-configs/dist/.htaccess')
			.pipe(plugins.replace(/# ErrorDocument/g, 'ErrorDocument'))
			.pipe(gulp.dest(dirs.dist));
});

gulp.task('copy:sql', function () {
	'use strict';
	return gulp.src('*.sql')
		.pipe(gulp.dest(dirs.dist));
});

gulp.task('copy:index.html', function () {
	'use strict';
	return gulp.src(dirs.src + '/index.html')
			.pipe(gulp.dest(dirs.dist));
});

gulp.task('copy:license', function () {
	'use strict';
	return gulp.src('LICENSE.txt')
			.pipe(gulp.dest(dirs.dist));
});

gulp.task('copy:misc', function () {
	'use strict';
	return gulp.src([

		// Copy all files
		dirs.src + '/**/*',

		// Exclude the following files
		// (other tasks will handle the copying of these files)
		// '!' + dirs.src + '/css/main.css',
		'!' + dirs.src + '/**/.DS_Store',
		'!' + dirs.src + '/index.html'

	], {

		// Include hidden files by default
		dot: true

	}).pipe(gulp.dest(dirs.dist));
});

gulp.task('copy:bootstrap', function () {
	'use strict';
	return gulp.src('node_modules/bootstrap/dist/css/bootstrap.min.css')
			.pipe(gulp.dest(dirs.dist + '/css'));
});

gulp.task('copy:normalize', function () {
	'use strict';
	return gulp.src('node_modules/normalize.css/normalize.css')
			.pipe(gulp.dest(dirs.dist + '/css'));
});

gulp.task('lint:js', function () {
	'use strict';
	return gulp.src([
		'gulpfile.js',
		dirs.src + '/js/*.js',
		dirs.test + '/*.js'
	]).pipe(plugins.jscs())
			.pipe(plugins.jshint())
			.pipe(plugins.jshint.reporter('jshint-stylish'));
});

gulp.task('lint:php', function (cb) {
	'use strict';
	return phplint([
		'*.php',
		dirs.src + '/**/*.php',
		dirs.src + '!lib/**/*'],
		{ limit: 10 },
		function (err) {
			if (err) {
				cb(err);
			} else {
				cb();
			}
		});
});


// ---------------------------------------------------------------------
// | Main tasks                                                        |
// ---------------------------------------------------------------------

gulp.task('archive', function (done) {
	'use strict';
	runSequence(
		'build',
		'archive:create_archive_dir',
		'archive:zip',
		done);
});

gulp.task('build', function (done) {
	'use strict';
	runSequence(
		['clean', 'lint:js', 'lint:php'],
		'copy',
		done);
});

gulp.task('default', ['archive']);
