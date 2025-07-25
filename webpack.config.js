const path = require('path');
const webpack = require('webpack');
const AssetsManifestPlugin = require('webpack-assets-manifest');
const ForkTsCheckerWebpackPlugin = require('fork-ts-checker-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
    cache: true,
    target: 'web',
    mode: process.env.NODE_ENV,
    devtool: isProduction ? false : (process.env.DEVTOOL || 'eval-source-map'),
    performance: {
        hints: false,
    },
    entry: ['react-hot-loader/patch', './resources/scripts/index.tsx'],
    output: {
        path: path.join(__dirname, '/public/assets'),
        filename: isProduction ? 'bundle.[chunkhash:8].js' : 'bundle.[hash:8].js',
        chunkFilename: isProduction ? '[name].[chunkhash:8].js' : '[name].[hash:8].js',
        publicPath: (process.env.WEBPACK_PUBLIC_PATH || '/assets/'),
        crossOriginLoading: 'anonymous',
    },
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                exclude: /node_modules|\.spec\.tsx?$/,
                loader: 'babel-loader',
            },
            {
                test: /\.mjs$/,
                include: /node_modules/,
                type: 'javascript/auto',
            },
            {
                test: /\.css$/,
                use: [
                    { loader: 'style-loader' },
                    {
                        loader: 'css-loader',
                        options: {
                            modules: {
                                auto: true,
                                localIdentName: isProduction ? '[name]_[hash:base64:8]' : '[path][name]__[local]',
                                localIdentContext: path.join(__dirname, 'resources/scripts/components'),
                            },
                            sourceMap: !isProduction,
                            importLoaders: 1,
                        },
                    },
                    {
                        loader: 'postcss-loader',
                        options: { sourceMap: !isProduction },
                    },
                ],
            },
            {
                test: /\.(png|jp(e?)g|gif)$/,
                loader: 'file-loader',
                options: {
                    name: 'images/[name].[hash:8].[ext]',
                },
            },
            {
                test: /\.svg$/,
                loader: 'svg-url-loader',
            },
            {
                test: /\.js$/,
                enforce: 'pre',
                loader: 'source-map-loader',
            }
        ],
    },
    stats: {
        // Ignore warnings emitted by "source-map-loader" when trying to parse source maps from
        // JS plugins we use, namely brace editor.
        warningsFilter: [/Failed to parse source map/],
    },
    resolve: {
        extensions: ['.ts', '.tsx', '.js', '.json'],
        alias: {
            '@': path.join(__dirname, '/resources/scripts'),
            '@definitions': path.join(__dirname, '/resources/scripts/api/definitions'),
            '@feature': path.join(__dirname, '/resources/scripts/components/server/features'),
        },
        symlinks: false,
    },
    externals: {
        // Mark moment as an external to exclude it from the Chart.js build since we don't need to use
        // it for anything.
        moment: 'moment',
    },
    plugins: [
        new webpack.EnvironmentPlugin({
            NODE_ENV: 'development',
            DEBUG: process.env.NODE_ENV !== 'production',
            WEBPACK_BUILD_HASH: Date.now().toString(16),
        }),
        new AssetsManifestPlugin({ writeToDisk: true, publicPath: true, integrity: true, integrityHashes: ['sha384'] }),
        new ForkTsCheckerWebpackPlugin({
            typescript: {
                mode: 'write-references',
                diagnosticOptions: {
                    semantic: true,
                    syntactic: true,
                },
            },
            eslint: isProduction ? undefined : {
                files: `${path.join(__dirname, '/resources/scripts')}/**/*.{ts,tsx}`,
            }
        }),
        process.env.ANALYZE_BUNDLE ? new BundleAnalyzerPlugin({
            analyzerHost: '0.0.0.0',
            analyzerPort: 8081,
        }) : null
    ].filter(p => p),
    optimization: {
        usedExports: true,
        sideEffects: false,
        runtimeChunk: false,
        removeEmptyChunks: true,
        minimize: isProduction,
        minimizer: [
            new TerserPlugin({
                cache: isProduction,
                parallel: true,
                extractComments: false,
                terserOptions: {
                    mangle: true,
                    output: {
                        comments: false,
                    },
                },
            }),
        ],
    },
    watchOptions: {
        poll: 1000,
        ignored: /node_modules/,
    },
    devServer: {
        compress: true,
        contentBase: path.join(__dirname, '/public'),
        publicPath: process.env.WEBPACK_PUBLIC_PATH || '/assets/',
        allowedHosts: [
            '.pterodactyl.test',
        ],
        headers: {
            'Access-Control-Allow-Origin': '*',
        },
    },
};
