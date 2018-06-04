module.exports = {
    mode: 'development',
    performance: {
        hints: false,
    },
    entry: {
        main: './resources/assets/scripts/app.js',
    },
    output: {
        path: '/dist',
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
        ]
    },
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue.esm.js'
        },
        extensions: ['*', '.js', '.vue', '.json']
    },
};
