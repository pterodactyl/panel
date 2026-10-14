import classic from '@/themes/classic';
import { registerSkins } from '@/themes/skins';
import { registerViewLayer } from './registry';

/** Classic needs no load: its own views are lazy, so registering it is synchronous. */
export function registerClassicTheme(): void {
    registerViewLayer('classic', 'classic', classic.views ?? {});
    registerSkins('classic', classic.ui ?? {});
}
