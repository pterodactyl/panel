/** @vitest-environment jsdom */
import { afterAll, afterEach, beforeAll, describe, expect, it } from 'vitest';
import http from '@/api/http';
import { renderView, type RenderedView } from '@/sdk/testing';
import { getViewDefinition, type ViewName } from '@/views/registry';

const views: readonly ViewName[] = [
    'page.server.schedules',
    'server.schedules',
    'page.server.schedules.detail',
    'server.schedule',
    'server.schedule.tasks',
    'page.server.users',
    'server.subusers',
    'page.server.users.create',
    'server.subuserForm',
    'page.server.backups',
    'server.backups',
    'page.server.settings',
    'server.sftp',
    'server.debug',
    'server.rename',
    'server.reinstall',
    'server.conflict',
];

const requests: string[] = [];
const adapter = http.defaults.adapter;
let rendered: RenderedView | undefined;

beforeAll(() => {
    http.defaults.adapter = async (config) => {
        requests.push(config.url ?? '');

        throw new Error(`A fixture requested ${config.url}.`);
    };
});

afterEach(() => {
    rendered?.unmount();
    rendered = undefined;
    requests.length = 0;
});

afterAll(() => {
    http.defaults.adapter = adapter;
});

describe.each(views)('%s', (name) => {
    it.each(Object.keys(getViewDefinition(name)?.fixtures ?? {}))(
        'renders its %s fixture without a backend',
        async (fixture) => {
            rendered = renderView(name, { fixture });
            await new Promise((resolve) => setTimeout(resolve, 0));

            expect(requests).toEqual([]);
        }
    );
});

describe('page compositions', () => {
    it('lays the settings page out from its blocks', () => {
        rendered = renderView('page.server.settings');

        const titles = [...rendered.container.querySelectorAll('[data-ui="card"][data-part="title"]')].map(
            (title) => title.textContent
        );

        expect(titles).toEqual(['SFTP Details', 'Debug Information', 'Change Server Details', 'Reinstall Server']);
    });

    it('places the schedule tasks inside the schedule box', () => {
        rendered = renderView('page.server.schedules.detail');

        const table = rendered.container.querySelector('[data-ui="table"][data-part="root"]');

        expect(table).toHaveClass('rounded-t-none');
        expect(table?.closest('.shadow-sm')?.textContent).toContain('Nightly restart');
        expect(rendered.container.textContent).toContain('Run Now');
    });

    it('shows the error of a page instead of its blocks', () => {
        rendered = renderView('page.server.backups', { fixture: 'error' });

        expect(rendered.container.textContent).toContain('Request failed with status code 500.');
        expect(rendered.container.querySelector('[data-ui="table"]')).toBeNull();
    });
});
