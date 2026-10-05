module.exports = {
    plugins: {
        // Tailwind CSS v4's PostCSS plugin handles @import inlining, CSS nesting,
        // and vendor prefixing on its own, so the old postcss-import / nesting /
        // autoprefixer / preset-env stack is no longer needed.
        '@tailwindcss/postcss': {},
    },
};
