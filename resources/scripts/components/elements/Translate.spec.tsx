// @vitest-environment jsdom
import { cleanup, render, screen } from '@testing-library/react';
import { createInstance } from 'i18next';
import { I18nextProvider } from 'react-i18next';
import { afterEach, expect, it } from 'vitest';
import Translate from './Translate';

afterEach(cleanup);

it('translates a catalogue sentence with markup around its values', async () => {
    const i18n = createInstance();

    await i18n.init({
        lng: 'de',
        nsSeparator: false,
        interpolation: { escapeValue: false },
        resources: {
            de: {
                translation: {
                    'Delete <bold>{{name}}</bold> permanently?': '<bold>{{name}}</bold> endgültig löschen?',
                },
            },
        },
    });

    render(
        <I18nextProvider i18n={i18n}>
            <Translate
                i18nKey='Delete <bold>{{name}}</bold> permanently?'
                values={{ name: 'Lobby' }}
                components={{ bold: <strong /> }}
            />
        </I18nextProvider>
    );

    expect(screen.getByText('Lobby').tagName).toBe('STRONG');
    expect(document.body.textContent).toBe('Lobby endgültig löschen?');
});

it.each(['a<b', 'Forge <1.12', 'x</bold>y', '<bold>z</bold>', 'a &amp; b'])(
    'keeps the value %s as literal text',
    async (name) => {
        const i18n = createInstance();

        await i18n.init({ lng: 'en', nsSeparator: false, interpolation: { escapeValue: false }, resources: {} });

        const { container } = render(
            <I18nextProvider i18n={i18n}>
                <Translate
                    i18nKey='Delete <bold>{{name}}</bold> ({{count}})?'
                    values={{ name, count: 2 }}
                    components={{ bold: <strong /> }}
                />
            </I18nextProvider>
        );

        expect(container.querySelectorAll('strong')).toHaveLength(1);
        expect(container.querySelector('strong')?.textContent).toBe(name);
        expect(container.textContent).toBe(`Delete ${name} (2)?`);
    }
);

it('wraps every text value, nested and listed ones included, and keeps counts for plurals', async () => {
    const i18n = createInstance();

    await i18n.init({
        lng: 'en',
        nsSeparator: false,
        interpolation: { escapeValue: false },
        resources: {
            en: {
                translation: {
                    'Deleted {{files}} from {{server.name}}_one': 'Deleted {{files}} from {{server.name}}',
                    'Deleted {{files}} from {{server.name}}_other':
                        'Deleted {{count}} files ({{files}}) from {{server.name}}',
                },
            },
        },
    });

    const { container } = render(
        <I18nextProvider i18n={i18n}>
            <Translate
                i18nKey='Deleted {{files}} from {{server.name}}'
                values={{ files: ['a<b', 'c'], count: 2, server: { name: '</strong>x' } }}
                valueComponent={<strong />}
            />
        </I18nextProvider>
    );

    expect([...container.querySelectorAll('strong')].map((node) => node.textContent)).toEqual([
        'a<b',
        'c',
        '</strong>x',
    ]);
    expect(container.textContent).toBe('Deleted 2 files (a<b,c) from </strong>x');
});
