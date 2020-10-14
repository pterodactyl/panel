module.exports = {
    twin: {
        preset: 'styled-components',
        autoCssProp: true,
        config: './tailwind.config.js', //todo: needs to by dynamic or use a central config
    },
    styledComponents: {
        pure: true,
        displayName: false,
        fileName: false,
    },
};
