import { defineThemeConfig } from '@pterodactyl/sdk/vite';

// A theme of CSS alone: no entry, so the build writes dist/theme.css and dist/theme.json.
export default defineThemeConfig({ stylesheet: 'src/theme.css' });
