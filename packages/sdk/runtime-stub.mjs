/**
 * "@pterodactyl/sdk" is provided BY THE PANEL at runtime through its import map -
 * extension bundles must treat it as external (the vite preset does this for you).
 * If this file executes, the SDK was bundled into an extension by mistake.
 */
throw new Error(
    '@pterodactyl/sdk is resolved at runtime by the panel import map. ' +
        'Build your extension with the preset from "@pterodactyl/sdk/vite" so the SDK stays external.'
);
