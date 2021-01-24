var babel = require('gulp-babel')
var gulp = require('gulp')
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');

gulp.task('craftwarmer', function() {
  return gulp
    .src(['src/Assets/src/warmer.js', 'src/Assets/src/modal.js'])
    .pipe(concat('craftwarmer.js'))
    .pipe(babel({ presets: ['@babel/env'] }))
    .pipe(uglify())
    .pipe(gulp.dest('src/Assets/dist'))
})

gulp.task('build', gulp.parallel('craftwarmer'));

gulp.task('watch', function(){
  gulp.watch('src/Assets/src/*.js', gulp.series(['build']));
})