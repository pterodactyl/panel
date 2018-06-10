const _ = require('lodash');
const path = require('path');
const tailwind = require('tailwindcss');
const glob = require('glob-all');

const AssetsManifestPlugin = require('webpack-assets-manifest');
const CleanPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const ShellPlugin = require('webpack-shell-plugin');
const PurgeCssPlugin = require('purgecss-webpack-plugin');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');

// Custom PurgeCSS extractor for Tailwind that allows special characters in
// class names.
//
// https://github.com/FullHuman/purgecss#extractor
class TailwindExtractor {
    static extract (content) {
        return content.match(/[A-z0-9-:\/]+/g) || [];
    }
}

const basePlugins = [
    new CleanPlugin(path.resolve(__dirname, 'public/assets')),
    new ShellPlugin({
        onBuildStart: [
            'php artisan vue-i18n:generate',
            'php artisan ziggy:generate resources/assets/scripts/helpers/ziggy.js',
        ],
    }),
    new ExtractTextPlugin('bundle-[hash].css', {
        allChunks: true,
    }),
    new AssetsManifestPlugin({
        writeToDisk: true,
        publicPath: true,
        integrity: true,
        integrityHashes: ['sha384'],
    }),
];

const productionPlugins = [
    new PurgeCssPlugin({
        paths: glob.sync([
            path.join(__dirname, 'resources/assets/scripts/**/*.vue'),
            path.join(__dirname, 'resources/themes/pterodactyl/**/*.blade.php'),
        ]),
        extractors: [
            {
                extractor: TailwindExtractor,
                extensions: ['html', 'js', 'php', 'vue'],
            }
        ],
    }),
    new UglifyJsPlugin({
        include: [
            path.join(__dirname, 'resources/assets/scripts'),
            path.join(__dirname, 'node_modules'),
            path.join(__dirname, 'vendor/tightenco'),
        ],
        cache: true,
        parallel: 2,
    }),
];

module.exports = {
    mode: process.env.NODE_ENV,
    devtool: process.env.NODE_ENV === 'production' ? false : 'source-map',
    performance: {
        hints: false,
    },
    // Passing an array loads them all but only exports the last.
    entry: ['./resources/assets/styles/main.css', './resources/assets/scripts/app.js'],
    output: {
        path: path.resolve(__dirname, 'public/assets'),
        filename: 'bundle-[hash].js',
        publicPath: _.get(process.env, 'PUBLIC_PATH', '') + '/assets/',
        crossOriginLoading: 'anonymous',
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
            },
            {
                test: /\.js$/,
                include: [
                    path.resolve(__dirname, 'resources'),
                ],
                loader: 'babel-loader?cacheDirectory',
            },
            {
                test: /\.css$/,
                include: [
                    path.resolve(__dirname, 'resources'),
                ],
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: [{
                        loader: 'css-loader',
                        options: {
                            sourceMap: true,
                            importLoaders: 1,
                        },
                    }, {
                        loader: 'postcss-loader',
                        options: {
                            ident: 'postcss',
                            sourceMap: true,
                            plugins: [
                                require('postcss-import'),
                                tailwind('./tailwind.js'),
                                require('postcss-preset-env')({stage: 0}),
                                require('precss'),
                                require('autoprefixer'),
                                require('cssnano'),
                            ]
                        },
                    }],
                }),
            }
        ]
    },
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue.esm.js'
        },
        extensions: ['.js', '.vue', '.json'],
        symlinks: false,
    },
    plugins: process.env.NODE_ENV === 'production' ? basePlugins.concat(productionPlugins) : basePlugins,
    serve: {
        content: "./public/",
        dev: {
            publicPath: "/assets/",
            headers: {
                "Access-Control-Allow-Origin": "*",
            },
        },
        hot: {
            hmr: true,
            reload: true,
        }
    }
};
