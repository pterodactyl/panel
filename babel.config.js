module.exports = function (api) {
    let targets = {};
    const plugins = [
        'babel-plugin-macros',
        'styled-components',
        'react-hot-loader/babel',
        '@babel/transform-runtime',
        '@babel/transform-react-jsx',
        '@babel/proposal-class-properties',
        '@babel/proposal-object-rest-spread',
        '@babel/proposal-optional-chaining',
        '@babel/proposal-nullish-coalescing-operator',
        '@babel/syntax-dynamic-import',
    ];

    if (api.env('test')) {
        targets = { node: 'current' };
        plugins.push('@babel/transform-modules-commonjs');
    }

    return {
        plugins,
        presets: [
            '@babel/typescript',
            ['@babel/env', {
                modules: false,
                useBuiltIns: 'entry',
                corejs: 3,
                targets,
            }],
            '@babel/react',
        ]
    };
};
