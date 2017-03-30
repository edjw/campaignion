/* eslint-env amd */

var gulp = require('gulp')
var watch = require('gulp-watch')
var rename = require('gulp-rename')
var babel = require('gulp-babel')

gulp.task('default', function () {
  return gulp.src('campaignion_*/**/*.es6.js')
    .pipe(babel())
    .pipe(rename(function (path) {
      path.basename = path.basename.replace(/\.es6$/, '')
    }))
    .pipe(gulp.dest(function (file) {
      return file.base
    }))
})

gulp.task('watch', function () {
  return watch('campaignion_*/**/*.es6.js')
    .pipe(babel())
    .pipe(rename(function (path) {
      path.basename = path.basename.replace(/\.es6$/, '')
    }))
    .pipe(gulp.dest(function (file) {
      return file.base
    }))
})
