'use strict';

var gulp = require('gulp-help')(require('gulp')),
    autoprefixer = require('gulp-autoprefixer'),
    cleanCSS = require('gulp-clean-css'),
    del = require('del'),
    fs = require('fs-extra'),
    imagemin = require('gulp-imagemin'),
    jshint = require('gulp-jshint'),
    notify = require('gulp-notify'),
    path = require('path'),
    preprocess = require('gulp-preprocess'),
    rename = require('gulp-rename'),
    sass = require('gulp-sass'),
    uglify = require('gulp-uglify'),
    watch = require('gulp-watch');


////////////////////////////////////////////////////////////////////////////////////////////////////
// Build functions
////////////////////////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////////////////////////
// Build tasks
////////////////////////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////////////////////////
// Clean tasks
////////////////////////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////////////////////////
// Release tasks
////////////////////////////////////////////////////////////////////////////////////////////////////

// Generate a release
gulp.task('release:make', 'Generate a release.', function() {
    var config = require(path.join(__dirname, 'package.json')),
        dist = path.join(__dirname, 'dist'),
        target = path.join(dist, 'leafpub-comments-' + config.version);

    // Delete the target directory if it exists
    del.sync(target);

    // Create dist directory
    fs.mkdirsSync(dist);

    // Copy app/ to dist/leafpub-<version>/
    fs.copySync(path.join(__dirname, 'app'), target);

    // Copy license and installation instructions
    fs.copySync(path.join(__dirname, 'LICENSE.md'), path.join(target, 'LICENSE.md'));
    fs.copySync(path.join(__dirname, 'INSTALL.md'), path.join(target, 'INSTALL.md'));

    // Inject version number into runtime.php
    try {
        fs.writeFileSync(
            path.join(target, 'source/runtime.php'),
            fs.readFileSync(path.join(target, 'source/runtime.php'))
                .toString()
                .replace('{{version}}', config.version)
        );
    } catch(err) {
        return console.error(err);
    }

    // Empty backups, content/cache
    del.sync(path.join(target, 'content/themes/*'));

    // Little message to celebrate
    console.log(
        '\nLeafpub Comments  ' + config.version + ' has been released! 🎉\n\n' +
        'Location: ' + target + '\n'
    );
});

// Clean releases
gulp.task('release:clean', 'Delete all generated releases.', function() {
    return del(path.join(__dirname, 'dist'));
});

////////////////////////////////////////////////////////////////////////////////////////////////////
// Other tasks
////////////////////////////////////////////////////////////////////////////////////////////////////

// Watch for changes
gulp.task('watch', 'Watch for script and style changes.', function() {
});

// Default
gulp.task('default', 'Run the default task.', ['watch']);
