import '@testing-library/jest-dom/vitest';
import { classicSkins } from '@/themes/classic/skins';
import { registerSkins } from '@/themes/skins';
import { initTestTranslations } from '@/i18nTesting';

// The panel registers classic before its first render; components under test render without booting.
registerSkins('classic', classicSkins);
initTestTranslations();
