const colors = require('tailwindcss/colors');

module.exports = {
    theme: {
        fontFamily: {
            sans: [ 'Rubik', '-apple-system', 'BlinkMacSystemFont', '"Helvetica Neue"', '"Roboto"', 'system-ui', 'sans-serif' ],
            header: [ '"IBM Plex Sans"', '"Roboto"', 'system-ui', 'sans-serif' ],
            mono: [ '"IBM Plex Mono"', '"Source Code Pro"', 'SourceCodePro', 'Menlo', 'Monaco', 'Consolas', 'monospace' ],
        },
        colors: {
            transparent: 'transparent',
            black: 'hsl(210, 27%, 10%)',
            white: '#fff',
            primary: colors.blue,
            neutral: colors.coolGray,
            cyan: colors.cyan,
            green: colors.green,
            yellow: colors.amber,
            red: colors.red,
        },
        extend: {
            fontSize: {
                '2xs': '0.625rem',
            },
            transitionDuration: {
                250: '250ms',
            },
            borderColor: theme => ({
                default: theme('colors.neutral.400', 'currentColor'),
            }),
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
    ]
};
