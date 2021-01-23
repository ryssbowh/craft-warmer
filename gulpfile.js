var babel = require('gulp-babel')
var gulp = require('gulp')
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');

gulp.task('utility', function() {
  return gulp
    .src(['src/Assets/src/warmer.js', 'src/Assets/src/modal.js', 'src/Assets/src/utility.js'])
    .pipe(concat('utility.js'))
    .pipe(babel({ presets: ['@babel/env'] }))
    .pipe(uglify())
    .pipe(gulp.dest('src/Assets/dist'))
})
gulp.task('front', function() {
  return gulp
    .src(['src/Assets/src/warmer.js', 'src/Assets/src/modal.js', 'src/Assets/src/front.js'])
    .pipe(concat('front.js'))
    .pipe(babel({ presets: ['@babel/env'] }))
    .pipe(uglify())
    .pipe(gulp.dest('src/Assets/dist'))
})

gulp.task('build', gulp.parallel('front', 'utility'));

gulp.task('watch', function(){
  gulp.watch('src/Assets/src/*.js', gulp.series(['build']));
})