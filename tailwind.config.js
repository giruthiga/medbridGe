import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                'med-black': '#0a0a0a',
                'med-card': '#131313',
                'med-card-2': '#1a1a1a',
                'med-hover': '#262626',
                'med-border': '#222222',
                'med-text': '#f5f5f5',
                'med-muted': '#8a8a8a',
                'med-green': '#10b981',
                'med-green-bright': '#34d399',
                'med-green-dim': '#064e3b',
            },
            boxShadow: {
                'glow-green': '0 0 40px -10px rgba(16, 185, 129, 0.5)',
                'glow-green-sm': '0 0 20px -5px rgba(16, 185, 129, 0.4)',
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
        },
    },

    plugins: [forms],
};