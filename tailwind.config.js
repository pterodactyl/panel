const colors = require('tailwindcss/colors');

module.exports = {
    content: [
        './resources/scripts/**/*.{js,ts,tsx}',
    ],
    theme: {
        extend: {
            fontFamily: {
                header: [ '"IBM Plex Sans"', '"Roboto"', 'system-ui', 'sans-serif' ],
            },
            colors: {
                black: '#131a20',
                // Deprecated, prefer the use of "blue" directly here.
                primary: colors.blue,
                neutral: colors.neutral,
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
        require('@tailwindcss/forms')({
            strategy: 'class',
        }),
    ]
};
