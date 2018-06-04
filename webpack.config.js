const path = require('path');
const ManifestPlugin = require('webpack-manifest-plugin');
const UglifyJsPLugin = require('uglifyjs-webpack-plugin');

module.exports = {
    mode: 'development',
    devtool: 'source-map',
    performance: {
        hints: false,
    },
    entry: {
        bundle: './resources/assets/scripts/app.js',
    },
    output: {
        path: path.resolve(__dirname, 'public/assets/scripts'),
        filename: 'bundle-[chunkhash].js',
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
                use: [{
                    loader: 'babel-loader',
                    options: {babelrc: true}
                }]
            },
        ]
    },
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue.esm.js'
        },
        extensions: ['*', '.js', '.vue', '.json']
    },
    plugins: [
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
