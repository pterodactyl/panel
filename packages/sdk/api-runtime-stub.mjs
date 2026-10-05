/**
 * "@pterodactyl/sdk/api" is provided BY THE PANEL at runtime through its import
 * map. Extension bundles must treat it as external (the vite preset does this).
 */
throw new Error(
    '@pterodactyl/sdk/api is resolved at runtime by the panel import map. ' +
        'Build your extension with the preset from "@pterodactyl/sdk/vite" so the SDK API runtime stays external.'
);
