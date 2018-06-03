const babel = require('gulp-babel');
const concat = require('gulp-concat');
const cssmin = require('gulp-cssmin');
const del = require('del');
const exec = require('child_process').exec;
const gulp = require('gulp');
const gulpif = require('gulp-if');
const postcss = require('gulp-postcss');
const rev = require('gulp-rev');
const uglify = require('gulp-uglify-es').default;
const webpackStream = require('webpack-stream');
const webpackConfig = require('./webpack.config.js');
const sourcemaps = require('gulp-sourcemaps');
const through = require('through2');

const argv = require('yargs')
    .default('production', false)
    .argv;

const paths = {
    manifest: './public/assets',
    assets: './public/assets/{css,scripts}/*.{css,js,map}',
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
        .pipe(sourcemaps.init())
        .pipe(postcss([
            require('postcss-import'),
            require('tailwindcss')('./tailwind.js'),
            require('precss'),
            require('postcss-preset-env')({stage: 0}),
            require('autoprefixer'),
        ]))
        .pipe(gulpif(argv.production, cssmin()))
        .pipe(concat('bundle.css'))
        .pipe(rev())
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.styles.dest))
        .pipe(rev.manifest(paths.manifest + '/manifest.json', {merge: true, base: paths.manifest}))
        .pipe(gulp.dest(paths.manifest));
}

/**
 * Build all of the waiting scripts.
 */
function scripts() {
    return webpackStream(webpackConfig)
        .pipe(sourcemaps.init({loadMaps: true}))
        .pipe(through.obj(function (file, enc, cb) { // Remove Souremaps
            if (!/\.map$/.test(file.path)) this.push(file);
            cb();
        }))
        .pipe(babel())
        .pipe(gulpif(argv.production, uglify()))
        .pipe(concat('bundle.js'))
        .pipe(rev())
        .pipe(sourcemaps.write('.'))
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
 * Generate the language files to be consumed by front end.
 *
 * @returns {Promise<any>}
 */
function i18n() {
    return new Promise((resolve, reject) => {
        exec('php artisan vue-i18n:generate', {}, (err, stdout, stderr) => {
            return err ? reject(err) : resolve({ stdout, stderr });
        })
    })
}

/**
 * Generate the routes file to be used in Vue files.
 * 
 * @returns {Promise<any>}
 */
function routes() {
    return new Promise((resolve, reject) => {
        exec('php artisan ziggy:generate resources/assets/scripts/helpers/ziggy.js', {}, (err, stdout, stderr) => {
            return err ? reject(err) : resolve({ stdout, stderr });
        });
    })
}

/**
 * Cleanup unused versions of hashed assets.
 */
function clean() {
    return del([paths.assets]);
}

exports.clean = clean;
exports.i18n = i18n;
exports.routes = routes;
exports.styles = styles;
exports.scripts = scripts;
exports.watch = watch;

gulp.task('components', gulp.parallel(i18n, routes));
gulp.task('scripts', gulp.series(clean, scripts));
gulp.task('default', gulp.series(clean, i18n, routes, styles, scripts));
