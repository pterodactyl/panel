const { blue, zinc, cyan } = require('tailwindcss/colors');

module.exports = {
    content: [
        './resources/scripts/**/*.{js,ts,tsx}',
    ],
    theme: {
        extend: {
            backgroundImage: {
                'storeone': "url('https://wallpapershome.com/images/pages/pic_v/13964.jpg')",
                'storetwo': "url('https://wallpapershome.com/images/pages/pic_v/13972.jpg')",
                'storethree': "url('https://wallpapershome.com/images/wallpapers/minecraft-4k-edition-1440x2560-e3-2017-xbox-one-x-screenshot-13960.jpg')",
            },
            colors: {
                black: '#000',
                // "primary" and "neutral" are deprecated.
                primary: blue,
                neutral: zinc,

                // Use cyan / gray instead.
                gray: zinc,
                cyan: cyan,
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
        require('@tailwindcss/line-clamp'),
        require('@tailwindcss/forms')({
            strategy: 'class',
        }),
    ]
};
