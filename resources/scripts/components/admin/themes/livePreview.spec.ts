// @vitest-environment jsdom

import { expect, it } from 'vitest';
import type { AdminThemeSetting } from '@/api/admin/themes/queries';
import { attributeValue, createLivePreview, tokenValue } from './livePreview';

const setting = (overrides: Partial<AdminThemeSetting>): AdminThemeSetting => ({
    key: 'probe',
    type: 'text',
    label: 'Probe',
    help: null,
    default: null,
    value: null,
    token: null,
    attribute: null,
    options: [],
    min: null,
    max: null,
    step: null,
    unit: null,
    max_length: null,
    accept: [],
    max_kilobytes: null,
    ...overrides,
});

it('writes token values the way the panel renders them', () => {
    expect(tokenValue(setting({ type: 'color' }), '#8b5cf6')).toBe('#8b5cf6');
    expect(tokenValue(setting({ type: 'number', unit: 'rem' }), 0.75)).toBe('0.75rem');
    expect(tokenValue(setting({ type: 'toggle' }), true)).toBe('1');
    expect(tokenValue(setting({ type: 'toggle' }), false)).toBe('0');
    expect(tokenValue(setting({ type: 'text' }), 'Say "hi"')).toBe('"Say \\"hi\\""');
    expect(tokenValue(setting({ type: 'image' }), '/extension-files/aurora/logo.png')).toBe(
        'url("/extension-files/aurora/logo.png")'
    );
    expect(tokenValue(setting({ type: 'color' }), null)).toBeNull();
    expect(attributeValue(false)).toBe('false');
    expect(attributeValue('')).toBeNull();
});

it('falls back to the default for a cleared value and restores what was there', () => {
    const root = document.createElement('html');
    const preview = createLivePreview(root);
    const accent = setting({ type: 'color', token: '--accent', default: '#8b5cf6' });

    root.style.setProperty('--accent', 'red');
    preview.apply(accent, '#000000');
    preview.apply(accent, '');
    expect(root.style.getPropertyValue('--accent')).toBe('#8b5cf6');

    preview.restore();
    expect(root.style.getPropertyValue('--accent')).toBe('red');

    preview.apply(accent, '#000000');
    preview.keep();
    preview.restore();
    expect(root.style.getPropertyValue('--accent')).toBe('#000000');
});
