import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
                display: ['"Barlow Condensed"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ink: '#071018',
                panel: '#101a24',
                raised: '#172633',
                line: '#2a3d4f',
                mist: '#93a4b8',
                snow: '#e8eef6',
                pitch: '#d6ff3f',
                flare: '#ff3b5c',
                amber: '#ffb020',
            },
        },
    },

    plugins: [forms],
};
