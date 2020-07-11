module.exports = {
    presets: [
        '@babel/typescript',
        ['@babel/env', {
            modules: false,
            useBuiltIns: 'entry',
            corejs: 3,
        }],
        '@babel/react',
    ],
    plugins: [
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
    ],
};
