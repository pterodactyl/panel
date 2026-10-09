// @vitest-environment jsdom
import { cleanup, renderHook } from '@testing-library/react';
import { I18nextProvider } from 'react-i18next';
import { createInstance } from 'i18next';
import { afterEach, expect, it } from 'vitest';
import type { ReactNode } from 'react';
import { ExtensionContext } from '@/extensions/context';
import { useExtensionTranslation } from './localization';

const mount = { extensionId: 'probe', context: 'translation test' };

afterEach(cleanup);
it('uses the mount namespace and host locale without reading another extension translations', async () => {
    const i18n = createInstance();

    await i18n.init({
        lng: 'en',
        resources: {
            en: { 'ext-probe::messages': { greeting: 'Hello {{name}}' }, 'ext-other::messages': { greeting: 'Wrong' } },
        },
    });
    function Wrapper({ children }: { children: ReactNode }) {
        return (
            <I18nextProvider i18n={i18n}>
                <ExtensionContext.Provider value={mount}>{children}</ExtensionContext.Provider>
            </I18nextProvider>
        );
    }

    const { result } = renderHook(() => useExtensionTranslation(), { wrapper: Wrapper });

    expect(result.current.ready).toBe(true);
    expect(result.current.locale).toBe('en');
    expect(result.current.t('greeting', { name: 'Alex' })).toBe('Hello Alex');
});
