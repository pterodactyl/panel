const babel = require('gulp-babel');
const concat = require('gulp-concat');
const cssmin = require('gulp-cssmin');
const del = require('del');
const gulp = require('gulp');
const gulpif = require('gulp-if');
const postcss = require('gulp-postcss');
const rev = require('gulp-rev');
const tailwindcss = require('tailwindcss');
const uglify = require('gulp-uglify');

const argv = require('yargs')
    .default('production', false)
    .argv;

const paths = {
    manifest: './public/assets',
    assets: './public/assets/{css,scripts}/*.{css,js}',
    styles: {
        src: './resources/assets/pterodactyl/css/**/*.css',
        dest: './public/assets/css',
    },
    scripts: {
        src: [
            './resources/assets/pterodactyl/scripts/**/*.js',
            './node_modules/jquery/dist/jquery.js',
        ],
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
            tailwindcss('./tailwind.js'),
            require('autoprefixer')]
        ))
        .pipe(gulpif(argv.production, cssmin()))
        .pipe(concat('bundle.css'))
        .pipe(rev())
        .pipe(gulp.dest(paths.styles.dest))
        .pipe(rev.manifest(paths.manifest + '/manifest.json', { merge: true, base: paths.manifest }))
        .pipe(gulp.dest(paths.manifest));
}

/**
 * Build all of the waiting scripts.
 */
function scripts() {
    return gulp.src(paths.scripts.src)
        .pipe(babel())
        .pipe(gulpif(argv.production, uglify()))
        .pipe(concat('bundle.js'))
        .pipe(rev())
        .pipe(gulp.dest(paths.scripts.dest))
        .pipe(rev.manifest(paths.manifest + '/manifest.json', { merge: true, base: paths.manifest }))
        .pipe(gulp.dest(paths.manifest));
}

/**
 * Proves watchers.
 */
function watch() {
    gulp.watch(paths.styles.src, styles);
    gulp.watch(paths.scripts.src, scripts);
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

gulp.task('default', gulp.series(clean, styles, scripts));
