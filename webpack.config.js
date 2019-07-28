/* eslint-disable @typescript-eslint/no-var-requires */
const _ = require('lodash');
const path = require('path');
const tailwind = require('tailwindcss');
const glob = require('glob-all');

const AssetsManifestPlugin = require('webpack-assets-manifest');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const PurgeCssPlugin = require('purgecss-webpack-plugin');
const ForkTsCheckerWebpackPlugin = require('fork-ts-checker-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

const isProduction = process.env.NODE_ENV === 'production';

let plugins = [
    new MiniCssExtractPlugin({ filename: isProduction ? 'bundle.[chunkhash:8].css' : 'bundle.[hash:8].css' }),
    new AssetsManifestPlugin({
        writeToDisk: true,
        publicPath: true,
        integrity: true,
        integrityHashes: ['sha384'],
    }),
    new ForkTsCheckerWebpackPlugin(),
];

if (isProduction) {
    plugins = plugins.concat([
        new PurgeCssPlugin({
            paths: glob.sync([
                path.join(__dirname, 'resources/scripts/**/*.ts'),
                path.join(__dirname, 'resources/themes/pterodactyl/**/*.blade.php'),
            ]),
            whitelistPatterns: [/^xterm/],
            extractors: [
                {
                    extractor: class {
                        static extract (content) {
                            return content.match(/[A-z0-9-:\/]+/g) || [];
                        }
                    },
                    extensions: ['html', 'ts', 'tsx', 'js', 'php'],
                },
            ],
        }),
    ]);
}

module.exports = {
    cache: true,
    target: 'web',
    mode: process.env.NODE_ENV,
    devtool: isProduction ? false : 'eval-source-map',
    performance: {
        hints: false,
    },
    entry: ['./resources/styles/main.css', './resources/scripts/index.tsx'],
    output: {
        path: path.resolve(__dirname, 'public/assets'),
        filename: isProduction ? 'bundle.[chunkhash:8].js' : 'bundle.[hash:8].js',
        chunkFilename: isProduction ? '[name].[chunkhash:8].js' : '[name].[hash:8].js',
        publicPath: _.get(process.env, 'PUBLIC_PATH', '') + '/assets/',
        crossOriginLoading: 'anonymous',
    },
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'babel-loader',
                        options: {
                            cacheDirectory: !isProduction,
                            presets: ['@babel/env', '@babel/react'],
                            plugins: [
                                'react-hot-loader/babel',
                                '@babel/plugin-syntax-dynamic-import',
                                ['styled-components', {
                                    displayName: true,
                                }],
                                'tailwind-components',
                            ],
                        },
                    },
                    {
                        loader: 'ts-loader',
                        options: {
                            experimentalWatchApi: true,
                            transpileOnly: true,
                        },
                    },
                ],
            },
            {
                test: /\.css$/,
                include: [
                    path.resolve(__dirname, 'resources'),
                ],
                use: [
                    { loader: MiniCssExtractPlugin.loader },
                    {
                        loader: 'css-loader',
                        options: {
                            sourceMap: !isProduction,
                            importLoaders: 1,
                        },
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
                        },
                    },
                ],
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
        extensions: ['.ts', '.tsx', '.js', '.json'],
        alias: {
            '@': path.join(__dirname, 'resources/scripts'),
            'react-dom': '@hot-loader/react-dom',
        },
        symlinks: false,
    },
    plugins: plugins,
    optimization: {
        minimize: isProduction,
        minimizer: [
            new TerserPlugin({
                cache: true,
                parallel: true,
                terserOptions: {
                    safari10: true,
                    mangle: true,
                    output: {
                        comments: false,
                    },
                },
            }),
        ],
    },
    devServer: {
        contentBase: path.join(__dirname, 'public'),
        publicPath: _.get(process.env, 'PUBLIC_PATH', '') + '/assets/',
        allowedHosts: [
            '.pterodactyl.test',
        ],
        headers: {
            'Access-Control-Allow-Origin': '*',
        },
    },
};
