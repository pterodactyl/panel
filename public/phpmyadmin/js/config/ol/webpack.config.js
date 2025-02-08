const path = require('path');

module.exports = {
    entry: './js/src/ol.mjs',
    devtool: 'source-map',
    mode: 'production',
    performance: {
        hints: false,
        maxEntrypointSize: 512000,
        maxAssetSize: 512000
    },
    output: {
        path: path.resolve('./js/vendor/openlayers'),
        filename: 'OpenLayers.js',
        library: 'ol',
        libraryTarget: 'umd',
        libraryExport: 'default',
    },
    optimization: {
        minimize: false,
    },
};
