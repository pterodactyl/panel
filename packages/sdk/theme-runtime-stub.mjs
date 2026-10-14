/**
 * "@pterodactyl/sdk/theme" is provided BY THE PANEL at runtime through its import
 * map. Theme and extension bundles must treat it as external (the vite preset does this).
 */
throw new Error(
    '@pterodactyl/sdk/theme is resolved at runtime by the panel import map. ' +
        'Build with the preset from "@pterodactyl/sdk/vite" so the theme runtime stays external.'
);
