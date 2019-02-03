const _ = require('lodash');
const path = require('path');
const tailwind = require('tailwindcss');
const glob = require('glob-all');

const AssetsManifestPlugin = require('webpack-assets-manifest');
const CleanPlugin = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const ShellPlugin = require('webpack-shell-plugin');
const PurgeCssPlugin = require('purgecss-webpack-plugin');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
const VueLoaderPlugin = require('vue-loader/lib/plugin');

const isProduction = process.env.NODE_ENV === 'production';

let plugins = [
    new CleanPlugin(path.resolve(__dirname, 'public/assets')),
    new ShellPlugin({
        onBuildStart: [
            'php artisan vue-i18n:generate',
            'php artisan ziggy:generate resources/assets/scripts/helpers/ziggy.js',
        ],
    }),
    new MiniCssExtractPlugin({ filename: 'bundle.[hash:8].css' }),
    new AssetsManifestPlugin({
        writeToDisk: true,
        publicPath: true,
        integrity: true,
        integrityHashes: ['sha384'],
    }),
    new VueLoaderPlugin(),
];

if (isProduction) {
    plugins = plugins.concat([
        new PurgeCssPlugin({
            paths: glob.sync([
                path.join(__dirname, 'resources/assets/scripts/**/*.vue'),
                path.join(__dirname, 'resources/assets/scripts/**/*.ts'),
                path.join(__dirname, 'resources/themes/pterodactyl/**/*.blade.php'),
            ]),
            // Don't let PurgeCSS remove classes ending with -enter or -leave-active
            // They're used by Vue transitions and are therefore not specifically defined
            // in any of the files are are checked by PurgeCSS.
            whitelistPatterns: [/-enter$/, /-leave-active$/],
            extractors: [
                {
                    extractor: class {
                        static extract (content) {
                            return content.match(/[A-z0-9-:\/]+/g) || [];
                        }
                    },
                    extensions: ['html', 'js', 'php', 'vue'],
                },
            ],
        }),
    ]);
}

const typescriptLoaders = [
    {
        loader: 'babel-loader',
        options: {
            cacheDirectory: !isProduction,
            presets: ['@babel/preset-env'],
            plugins: [
                '@babel/plugin-proposal-class-properties',
                ['@babel/plugin-proposal-object-rest-spread', { 'useBuiltIns': true }]
            ],
        },
    },
    {
        loader: 'ts-loader',
        options: {
            appendTsSuffixTo: [/\.vue$/],
            experimentalWatchApi: true,
        }
    }
];

const cssLoaders = [
    { loader: MiniCssExtractPlugin.loader },
    {
        loader: 'css-loader',
        options: {
            sourceMap: !isProduction,
            importLoaders: 1,
        }
    },
    { loader: 'resolve-url-loader' },
    {
        loader: 'postcss-loader',
        options: {
            ident: 'postcss',
            sourceMap: true,
            plugins: [
                require('postcss-import'),
                tailwind('./tailwind.js'),
                require('postcss-preset-env')({
                    stage: 2,
                }),
                require('precss'),
            ].concat(isProduction ? require('cssnano') : []),
        }
    }
];

module.exports = {
    mode: process.env.NODE_ENV,
    devtool: isProduction ? false : 'inline-source-map',
    performance: {
        hints: false,
    },
    entry: ['./resources/assets/styles/main.css', './resources/assets/scripts/app.ts'],
    output: {
        path: path.resolve(__dirname, 'public/assets'),
        filename: 'bundle.[hash:8].js',
        chunkFilename: 'chunk.[name].js',
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
                test: /\.ts$/,
                exclude: /node_modules/,
                use: typescriptLoaders,
            },
            {
                test: /\.css$/,
                include: [
                    path.resolve(__dirname, 'resources'),
                ],
                use: cssLoaders,
            },
            {
                test: /\.(png|jpg|gif|svg)$/,
                loader: 'file-loader',
                options: {
                    name: '[name].[ext]?[hash:8]',
                },
            },
        ],
    },
    resolve: {
        extensions: ['.ts', '.js', '.vue', '.json'],
        alias: {
            'vue$': 'vue/dist/vue.esm.js',
        },
        symlinks: false,
    },
    plugins: plugins,
    optimization: {
        minimize: true,
        minimizer: !isProduction ? [] : [
            new UglifyJsPlugin({
                cache: true,
                parallel: true,
                uglifyOptions: {
                    output: {
                        comments: false,
                    },
                },
            }),
        ],
    },
    devServer: {
        contentBase: path.join(__dirname, 'public'),
        publicPath: '/assets/',
        allowedHosts: [
            '.pterodactyl.test',
        ],
        headers: {
            'Access-Control-Allow-Origin': '*',
        },
    },
};
