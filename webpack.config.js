const path = require('path');
const AssetsManifestPlugin = require('webpack-assets-manifest');
const CleanPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const ShellPlugin = require('webpack-shell-plugin');
const MinifyPlugin = require('babel-minify-webpack-plugin');

module.exports = {
    mode: 'development',
    devtool: 'source-map',
    performance: {
        hints: false,
    },
    // Passing an array loads them all but only exports the last.
    entry: ['./resources/assets/styles/main.css', './resources/assets/scripts/app.js'],
    output: {
        path: path.resolve(__dirname, 'public/assets'),
        filename: 'bundle-[chunkhash].js',
        publicPath: '/assets/',
        crossOriginLoading: 'anonymous',
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader',
                options: {
                    postcss: [
                        require('postcss-import'),
                        require('postcss-preset-env')({stage: 0}),
                        require('tailwindcss')('./tailwind.js'),
                        require('autoprefixer'),
                    ]
                }
            },
            {
                test: /\.js$/,
                include: [
                    path.resolve(__dirname, 'resources'),
                ],
                loader: 'babel-loader',
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
                        options: {importLoaders: 1},
                    }, {
                        loader: 'postcss-loader',
                        options: {
                            ident: 'postcss',
                            plugins: [
                                require('postcss-import'),
                                require('postcss-preset-env')({stage: 0}),
                                require('tailwindcss')('./tailwind.js'),
                                require('autoprefixer'),
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
        extensions: ['*', '.js', '.vue', '.json']
    },
    plugins: [
        new CleanPlugin(path.resolve(__dirname, 'public/assets')),
        new ShellPlugin({
            onBuildStart: [
                'php artisan vue-i18n:generate',
                'php artisan ziggy:generate resources/assets/scripts/helpers/ziggy.js',
            ],
        }),
        new ExtractTextPlugin('bundle-[chunkhash].css', {
            allChunks: true,
        }),
        new MinifyPlugin({
            mangle: {topLevel: true},
        }, {
            include: [
                path.resolve(__dirname, 'resources'),
                path.resolve(__dirname, 'node_modules'),
            ],
        }),
        new AssetsManifestPlugin({
            writeToDisk: true,
            publicPath: true,
            integrity: true,
            integrityHashes: ['sha384'],
        }),
    ]
};
