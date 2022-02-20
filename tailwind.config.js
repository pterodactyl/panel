const colors = require('tailwindcss/colors');

module.exports = {
    content: [
        './resources/scripts/**/*.{js,ts,tsx}',
    ],
    theme: {
        fontFamily: {
            sans: [ 'Rubik', '-apple-system', 'BlinkMacSystemFont', '"Helvetica Neue"', '"Roboto"', 'system-ui', 'sans-serif' ],
            header: [ '"IBM Plex Sans"', '"Roboto"', 'system-ui', 'sans-serif' ],
            mono: [ '"IBM Plex Mono"', '"Source Code Pro"', 'SourceCodePro', 'Menlo', 'Monaco', 'Consolas', 'monospace' ],
        },
        extend: {
            colors: {
                primary: colors.blue,
                neutral: colors.slate,
                cyan: colors.cyan,
            },
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
