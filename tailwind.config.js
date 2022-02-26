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
                primary: {
                    50: '#e6f6ff',
                    100: '#b8e2ff',
                    200: '#7ac3fa',
                    300: '#49a4f3',
                    400: '#2487eb',
                    500: '#0967d3',
                    600: '#0550b3',
                    700: '#0345a0',
                    800: '#01337e',
                    900: '#002057',
                },
                neutral: {
                    50: '#f5f7fa',
                    100: '#e5e8eb',
                    200: '#cad1d8',
                    300: '#9aa5b1',
                    400: '#7b8793',
                    500: '#606d7b',
                    600: '#515f6c',
                    700: '#3f4d5a',
                    800: '#33404d',
                    900: '#1f2933',
                },
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
