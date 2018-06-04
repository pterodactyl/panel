const path = require('path');
const CleanPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const ManifestPlugin = require('webpack-manifest-plugin');
const ShellPlugin = require('webpack-shell-plugin');
const UglifyJsPLugin = require('uglifyjs-webpack-plugin');

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
                    path.resolve(__dirname, 'resources/assets/scripts'),
                ],
                loader: 'babel-loader',
            },
            {
                test: /\.css$/,
                include: [
                    path.resolve(__dirname, 'resources/assets/styles'),
                ],
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: ['css-loader', {
                        loader:  'postcss-loader',
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
        new UglifyJsPLugin({
            include: [
                path.resolve(__dirname, 'resources/assets/scripts'),
            ],
            parallel: 2,
            sourceMap: false,
            uglifyOptions: {
                ecma: 5,
                toplevel: true,
                safari10: true,
            }
        }),
        new ManifestPlugin(),
    ]
};
