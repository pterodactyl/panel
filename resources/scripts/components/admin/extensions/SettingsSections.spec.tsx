/** @vitest-environment jsdom */

import { cleanup, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it } from 'vitest';
import type { AdminExtensionSettingField } from '@/api/admin/extensions/queries';
import SettingsSections, { groupSettingsBySection } from './SettingsSections';

afterEach(cleanup);

const field = (input: string, tab: string | null): AdminExtensionSettingField => ({
    input,
    label: input,
    help: null,
    tab,
    field: 'text',
    options: [],
    value: null,
    constraints: { max_length: null, max_items: null, max_kilobytes: null, accept: [] },
    visibility: 'admin',
});

const names = (schema: AdminExtensionSettingField[]) =>
    groupSettingsBySection(schema).map((section) => [section.name, section.fields.map((field) => field.input)]);

const renderField = (setting: AdminExtensionSettingField) => <p key={setting.input}>{setting.input}</p>;

describe('groupSettingsBySection', () => {
    it('keeps a schema without tabs as one section', () => {
        expect(names([field('a', null), field('b', null)])).toEqual([['General', ['a', 'b']]]);
    });

    it('orders sections by their first field and puts untabbed fields under General first', () => {
        expect(
            names([
                field('bucket', 'Storage'),
                field('enabled', null),
                field('webhook', 'Notifications'),
                field('access_key', 'Storage'),
            ])
        ).toEqual([
            ['General', ['enabled']],
            ['Storage', ['bucket', 'access_key']],
            ['Notifications', ['webhook']],
        ]);
    });

    it('omits General when every field names a tab', () => {
        expect(names([field('bucket', 'Storage'), field('webhook', 'Notifications')]).map(([name]) => name)).toEqual([
            'Storage',
            'Notifications',
        ]);
    });
});

describe('SettingsSections', () => {
    it('renders fields in the scroll area without a section picker when no field names a tab', () => {
        render(<SettingsSections schema={[field('a', null), field('b', null)]} renderField={renderField} />);

        expect(screen.queryByLabelText('Section')).toBeNull();
        expect(screen.getByText('a')).toBeVisible();
        expect(screen.getByText('b')).toBeVisible();
    });

    it('switches sections from the dropdown and keeps hidden sections mounted', async () => {
        const user = userEvent.setup();

        render(
            <SettingsSections
                schema={[field('enabled', null), field('bucket', 'Storage'), field('access_key', 'Storage')]}
                renderField={renderField}
            />
        );

        expect(screen.getByLabelText('Section')).toHaveValue('General');
        expect(screen.getByText('enabled')).toBeVisible();
        expect(screen.getByText('bucket')).not.toBeVisible();

        await user.click(screen.getByLabelText('Section'));
        await user.click(await screen.findByRole('option', { name: 'Storage' }));

        expect(screen.getByLabelText('Section')).toHaveValue('Storage');
        expect(screen.getByText('bucket')).toBeVisible();
        expect(screen.getByText('access_key')).toBeVisible();
        expect(screen.getByText('enabled')).not.toBeVisible();
    });
});
