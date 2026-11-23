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
