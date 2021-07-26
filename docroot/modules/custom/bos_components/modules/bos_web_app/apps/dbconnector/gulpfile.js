var gulp = require('gulp'),
  concat = require('gulp-concat'),
  uglify = require('gulp-uglify');

gulp.task('minify', function () {
   gulp.src(['src/js/server.js', 'src/models/**/*.js', 'src/common/**/*.js'])
      .pipe(uglify())
      .pipe(concat('dbconnector.js'))
      .pipe(gulp.dest('dist'))
});
