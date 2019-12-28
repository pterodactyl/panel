/* eslint-disable @typescript-eslint/no-var-requires */
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
];

if (isProduction) {
    plugins = plugins.concat([
        new PurgeCssPlugin({
            paths: glob.sync([
                path.join(__dirname, 'resources/scripts/**/*.tsx'),
                path.join(__dirname, 'resources/views/templates/**/*.blade.php'),
            ]),
            whitelistPatterns: [/^xterm/],
            extractors: [
                {
                    extractor: class {
                        static extract (content) {
                            return content.match(/[A-Za-z0-9-_:\\/]+/g) || [];
                        }
                    },
                    extensions: ['html', 'ts', 'tsx', 'js', 'php'],
                },
            ],
        }),
    ]);
} else {
    plugins.concat([new ForkTsCheckerWebpackPlugin()]);
}

module.exports = {
    cache: true,
    target: 'web',
    mode: process.env.NODE_ENV,
    devtool: isProduction ? false : process.env.DEVTOOL || 'source-map',
    performance: {
        hints: false,
    },
    entry: [
        'react-hot-loader/patch',
        './resources/styles/main.css',
        './resources/scripts/index.tsx',
    ],
    output: {
        path: path.resolve(__dirname, 'public/assets'),
        filename: isProduction ? 'bundle.[chunkhash:8].js' : 'bundle.[hash:8].js',
        chunkFilename: isProduction ? '[name].[chunkhash:8].js' : '[name].[hash:8].js',
        publicPath: (process.env.PUBLIC_PATH || '') + '/assets/',
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
                            presets: [
                                '@babel/typescript',
                                '@babel/env',
                                '@babel/react',
                            ],
                            plugins: [
                                'tailwind-components',
                                'react-hot-loader/babel',
                                '@babel/transform-runtime',
                                '@babel/proposal-class-properties',
                                '@babel/proposal-object-rest-spread',
                                '@babel/syntax-dynamic-import',
                            ],
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
                    {
                        loader: MiniCssExtractPlugin.loader,
                        options: {
                            hmr: !isProduction,
                        },
                    },
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
    watchOptions: {
        ignored: /node_modules/,
    },
    devServer: {
        contentBase: path.join(__dirname, 'public'),
        publicPath: (process.env.PUBLIC_PATH || '') + '/assets/',
        allowedHosts: [
            '.pterodactyl.test',
        ],
        headers: {
            'Access-Control-Allow-Origin': '*',
        },
    },
};
