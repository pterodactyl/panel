// @vitest-environment jsdom
import { cleanup, renderHook } from '@testing-library/react';
import { I18nextProvider } from 'react-i18next';
import { createInstance } from 'i18next';
import { afterEach, expect, it } from 'vitest';
import type { ReactNode } from 'react';
import { ExtensionContext } from '@/extensions/context';
import { useExtensionTranslation, useTranslation } from './localization';

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

it('translates interface text keyed by its english, falling back to the english itself', async () => {
    const i18n = createInstance();

    await i18n.init({
        lng: 'de',
        fallbackLng: 'en',
        nsSeparator: false,
        interpolation: { escapeValue: false },
        resources: {
            de: {
                translation: {
                    'Login to Continue': 'Anmelden, um fortzufahren',
                    'Welcome back, {{name}}': 'Willkommen zurück, {{name}}',
                    'Error: try again.': 'Fehler: erneut versuchen.',
                    '{{count}} file_one': '{{count}} Datei',
                    '{{count}} file_other': '{{count}} Dateien',
                },
            },
        },
    });
    function Wrapper({ children }: { children: ReactNode }) {
        return <I18nextProvider i18n={i18n}>{children}</I18nextProvider>;
    }

    const { result } = renderHook(() => useTranslation(), { wrapper: Wrapper });

    expect(result.current.locale).toBe('de');
    expect(result.current.t('Login to Continue')).toBe('Anmelden, um fortzufahren');
    expect(result.current.t('Welcome back, {{name}}', { name: 'Alex' })).toBe('Willkommen zurück, Alex');
    expect(result.current.t('Error: try again.')).toBe('Fehler: erneut versuchen.');
    expect(result.current.t('{{count}} file', { count: 2 })).toBe('2 Dateien');
    expect(result.current.t('Not translated yet.')).toBe('Not translated yet.');
});
