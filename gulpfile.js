var gulp = require('gulp');
var rename = require('gulp-rename');
var notify = require('gulp-notify');
var uglify = require('gulp-uglify');
var gutil = require('gulp-util');
var cache = require('gulp-cached');

gulp.task('scripts', function() {
  return gulp.src(['core/ui/**/*.js', '!core/ui/**/*.min.js'])
  .pipe(cache('scripts'))
  .pipe(uglify().on('error', gutil.log))
  .pipe(rename({ extname: '.min.js' }))
  .pipe(gulp.dest('core/ui'))
  .pipe(notify({ message: 'Scripts task complete' }));
});

// Watch
gulp.task('watch', function() {
    gulp.watch('core/ui/**/!(*.min)*.js', ['scripts']);
});

// Default task
gulp.task('default', ['scripts', 'watch']);
