
var fs = require('fs');
var path = require('path');

var gulp = require('gulp');
var plugins = require('gulp-load-plugins')(); // Load all gulp plugins automatically

var runSequence = require('run-sequence');    // Temporary solution until gulp 4 (https://github.com/gulpjs/gulp/issues/355)

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

	archiver.on('error', function (error) {
		done();
		throw error;
	});

	output.on('close', done);

	files.forEach(function (file) {

		var filePath = path.resolve(dirs.dist, file);

		// `archiver.bulk` does not maintain the file
		// permissions, so we need to add files individually
		archiver.append(fs.createReadStream(filePath), {
			'name': file,
			'mode': fs.statSync(filePath)
		});

	});

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
	'copy:index.html',
	'copy:license',
	'copy:misc',
	'copy:normalize'
]);

gulp.task('copy:.htaccess', function () {
	'use strict';
	return gulp.src('node_modules/apache-server-configs/dist/.htaccess')
			.pipe(plugins.replace(/# ErrorDocument/g, 'ErrorDocument'))
			.pipe(gulp.dest(dirs.dist));
});

gulp.task('copy:index.html', function () {
	'use strict';
	return gulp.src(dirs.src + '/index.html')
			.pipe(plugins.replace(/{{JQUERY_VERSION}}/g, pkg.devDependencies.jquery))
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
		'!' + dirs.src + '/index.html'

	], {

		// Include hidden files by default
		dot: true

	}).pipe(gulp.dest(dirs.dist));
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
			['clean', 'lint:js'],
			'copy',
			done);
});

gulp.task('default', ['build']);
