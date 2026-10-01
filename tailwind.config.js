const withAlpha = (token) => `rgb(var(--color-${token}-rgb) / <alpha-value>)`;
const palette = (token, softToken) => ({
    50: withAlpha(softToken),
    100: withAlpha(softToken),
    200: withAlpha(softToken),
    300: withAlpha(token),
    400: withAlpha(token),
    500: withAlpha(token),
    600: withAlpha(token),
    700: withAlpha(token),
    800: withAlpha(token),
    900: withAlpha(token),
});

const gray = {
    50: withAlpha('text'),
    100: withAlpha('text'),
    200: withAlpha('text-muted'),
    300: withAlpha('text-muted'),
    400: withAlpha('border'),
    500: withAlpha('border'),
    600: withAlpha('surface-raised'),
    700: withAlpha('surface-raised'),
    800: withAlpha('surface'),
    900: withAlpha('background'),
};

const primary = {
    50: withAlpha('text'),
    100: withAlpha('secondary'),
    200: withAlpha('secondary'),
    300: withAlpha('primary'),
    400: withAlpha('primary'),
    500: withAlpha('accent'),
    600: withAlpha('primary'),
    700: withAlpha('secondary'),
    800: withAlpha('surface-raised'),
    900: withAlpha('background'),
};

const success = palette('success', 'success-soft');
const danger = palette('error', 'error-soft');
const accent = palette('accent', 'secondary');

module.exports = {
    content: [
        './resources/scripts/**/*.{js,ts,tsx}',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['var(--font-main)'],
                header: ['var(--font-main)'],
            },
            colors: {
                black: 'var(--color-black)',
                // "primary" and "neutral" are deprecated, prefer the use of "blue" and "gray"
                 
                primary: primary,
                gray: gray,
                neutral: gray,
                cyan: accent,
                green: success,
                red: danger,
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
