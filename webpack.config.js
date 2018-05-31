module.exports = {
    entry: './resources/assets/scripts/app.js',
    output: {
        filename: 'webpack.build.js',
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
                exclude: /(node_modules|vendor)/,
                use: [{
                    loader: "babel-loader",
                    options: { presets: ['es2015'] }
                }]
            },
            {
                test: /\.pug$/,
                loader: 'pug-plain-loader'
            },
        ]
    },
    resolve: {
        alias: {
            // 'vue': 'vue/dist/vue.js'
            'vue$': 'vue/dist/vue.esm.js'
        },
        extensions: ['*', '.js', '.vue', '.json']
    },
    plugins: [],
    devtool: 'source-map',
};
