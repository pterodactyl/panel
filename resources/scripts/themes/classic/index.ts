import { defineTheme } from '@/sdk/theme';
import { classicSkins } from './skins';
import { classicViews } from './views';

/** The stock panel UI, built only on the public theme API. */
export default defineTheme({ ui: classicSkins, views: classicViews });
