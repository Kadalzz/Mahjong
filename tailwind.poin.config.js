const token = (names, prefix) => Object.fromEntries(names.map((name) => [name, `var(--${prefix}-${name})`]));

const colors = {
    transparent: 'transparent',
    current: 'currentColor',
    brand: token(['green', 'green-deep', 'red', 'red-light'], 'brand'),
    accent: token(['gold', 'gold-light'], 'accent'),
    button: token(['primary', 'primary-hover', 'primary-pressed', 'primary-border', 'text'], 'button'),
    neutral: token(['cream', 'cream-dark', 'white'], 'neutral'),
    text: token(['ink', 'mute'], 'text'),
    status: token(['success', 'success-bg'], 'status'),
    alpha: token([
        'cream-4', 'cream-7', 'cream-10', 'cream-14', 'cream-45', 'cream-50', 'cream-55', 'cream-65', 'cream-70',
        'gold-0', 'gold-25', 'gold-40', 'gold-45', 'gold-50', 'gold-55', 'gold-60', 'gold-70',
        'gold-light-40', 'gold-light-60', 'ink-14', 'mute-0', 'mute-35', 'red-light-0',
    ], 'alpha'),
};

export default {
    content: [
        './resources/views/layouts/poin.blade.php',
        './resources/views/components/poin/**/*.blade.php',
        './resources/views/poin/**/*.blade.php',
        './resources/views/dev/**/*.blade.php',
    ],

    theme: {
        colors,
        spacing: {
            0: '0',
            px: '1px',
            ...token(['0-5', '1', '1-5', '2', '2-5', '3', '3-5', '4', '4-5', '6', '7', '8', '9', '10', '12', '16'], 'space'),
        },
        borderRadius: {
            none: '0',
            ...token(['xs', 'sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', 'full'], 'radius'),
        },
        borderWidth: {
            0: '0',
            DEFAULT: 'var(--stroke-hairline)',
            ...token(['hairline', 'thin', 'base', 'emphasis', 'bold'], 'stroke'),
        },
        fontFamily: {
            display: 'var(--font-family-display)',
            body: 'var(--font-family-body)',
        },
        fontSize: token([
            'display', 'h1', 'h2', 'h3', 'h4', 'h5', 'title-lg', 'title-md', 'title-sm', 'button', 'label',
            'body-lg', 'body', 'body-sm', 'caption-bold', 'tab', 'kicker', 'pill', 'caption',
        ], 'font-size'),
        boxShadow: {
            none: 'none',
            ...token([
                'elevation-low', 'elevation-medium', 'elevation-high', 'elevation-highest',
                'glow-primary', 'glow-primary-hover', 'glow-win-hover', 'glow-red', 'glow-success', 'glow-scan',
                'inset-pressed', 'inset-soft', 'depth-primary', 'depth-primary-hover', 'depth-primary-pressed', 'depth-locked',
            ], 'shadow'),
        },
        extend: {
            maxWidth: { screen: 'var(--screen-width)' },
            gap: { stack: 'var(--stack-gap)' },
        },
    },

    plugins: [],
};
