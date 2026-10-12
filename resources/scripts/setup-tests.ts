import '@testing-library/jest-dom/vitest';
import { classicSkins } from '@/themes/classic/skins';
import { registerSkins } from '@/themes/skins';

// The panel registers classic before its first render; components under test render without booting.
registerSkins('classic', classicSkins);
