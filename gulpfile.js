const babel = require('gulp-babel');
const concat = require('gulp-concat');
const cssmin = require('gulp-cssmin');
const del = require('del');
const gulp = require('gulp');
const gulpif = require('gulp-if');
const postcss = require('gulp-postcss');
const rev = require('gulp-rev');
const uglify = require('gulp-uglify-es').default;
const webpackStream = require('webpack-stream');
const webpackConfig = require('./webpack.config.js');

const argv = require('yargs')
    .default('production', false)
    .argv;

const paths = {
    manifest: './public/assets',
    assets: './public/assets/{css,scripts}/*.{css,js}',
    styles: {
        src: './resources/assets/styles/main.css',
        dest: './public/assets/css',
    },
    scripts: {
        src: './resources/assets/scripts/**/*.{js,vue}',
        watch: ['./resources/assets/scripts/**/*.{js,vue}', './resources/lang/locales.js'],
        dest: './public/assets/scripts',
    },
};

/**
 * Build un-compiled CSS into a minified version.
 */
function styles() {
    return gulp.src(paths.styles.src)
        .pipe(postcss([
            require('postcss-import'),
            require('tailwindcss')('./tailwind.js'),
            require('postcss-preset-env')({stage: 0}),
            require('autoprefixer'),
        ]))
        .pipe(gulpif(argv.production, cssmin()))
        .pipe(concat('bundle.css'))
        .pipe(rev())
        .pipe(gulp.dest(paths.styles.dest))
        .pipe(rev.manifest(paths.manifest + '/manifest.json', {merge: true, base: paths.manifest}))
        .pipe(gulp.dest(paths.manifest));
}

/**
 * Build all of the waiting scripts.
 */
function scripts() {
    return webpackStream(webpackConfig)
        .pipe(babel())
        .pipe(gulpif(argv.production, uglify()))
        .pipe(concat('bundle.js'))
        .pipe(rev())
        .pipe(gulp.dest(paths.scripts.dest))
        .pipe(rev.manifest(paths.manifest + '/manifest.json', {merge: true, base: paths.manifest}))
        .pipe(gulp.dest(paths.manifest));
}

/**
 * Provides watchers.
 */
function watch() {
    gulp.watch(['./resources/assets/styles/**/*.css'], gulp.series(function cleanStyles() {
        return del(['./public/assets/css/**/*.css']);
    }, styles));

    gulp.watch(paths.scripts.watch, gulp.series(function cleanScripts() {
        return del(['./public/assets/scripts/**/*.js']);
    }, scripts));
}

/**
 * Cleanup unused versions of hashed assets.
 */
function clean() {
    return del([paths.assets]);
}

exports.clean = clean;
exports.styles = styles;
exports.scripts = scripts;
exports.watch = watch;

gulp.task('scripts', gulp.series(clean, scripts));
gulp.task('default', gulp.series(clean, styles, scripts));
