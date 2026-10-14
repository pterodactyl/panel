import { defineThemeConfig } from '@pterodactyl/sdk/vite';

// Aurora is found in the panel's extensions directory, then in node_modules.
export default defineThemeConfig({ entry: 'src/theme.tsx', stylesheet: 'src/theme.css', extensions: '..' });
