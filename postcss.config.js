module.exports = {
    plugins: [
        require('postcss-import'),
         
         
         
        // @see https://github.com/csstools/postcss-plugins/tree/main/plugins/postcss-nesting
        require('tailwindcss/nesting')(require('postcss-nesting')),
        require('tailwindcss'),
        require('autoprefixer'),
        require('postcss-preset-env')({
            features: {
                'nesting-rules': false,
            },
        }),
    ],
};
