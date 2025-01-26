const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: "Noto Sans JP",
                serif: "Noto Serif JP",
                kaku:  "Zen Kaku Gothic New",
                yuji: "Yuji Syuku"
            },
            colors:{
                back: 'rgb(236 240 245)',
                lback: 'rgb(249 250 252)',
                txt: 'rgb(153 153 153)'
            },
            fontSize: {
                12: '12px',
                14: '14px',
                15: '15px',
                16: '16px',
                24: '24px',
            },
            fontWeight: {
                300: 300,
                400: 400,
                500: 500,
                600: 600,
                700: 700,
                900: 900,
            }
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
