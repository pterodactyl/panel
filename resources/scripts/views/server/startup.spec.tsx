// @vitest-environment jsdom

import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import type { AxiosAdapter } from 'axios';
import type { PropsWithChildren } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { serverStartupQueryOptions, type ServerStartupVariable } from '@/api/server/startup/queries';
import {
    commitExtensionRegistryBatch,
    createExtensionRegistryBatch,
    prepareExtensions,
    registerSlotComponent,
    type SlotComponentProps,
    type StartupFormSlotData,
} from '@/extensions/registry';
import { createExtensionTestHost, createTestServer, type ExtensionTestHost } from '@/sdk/testing';
import { View } from '../View';

const mocks = vi.hoisted(() => ({ error: vi.fn(), copy: vi.fn<(text: string) => Promise<boolean>>() }));

vi.mock('@/plugins/notifications', () => ({ notifyHttpError: mocks.error }));
vi.mock('@/lib/clipboard', () => ({ default: mocks.copy }));
vi.mock('@/components/elements/InputSpinner', () => ({
    default: ({ visible, children }: PropsWithChildren<{ visible: boolean }>) => (
        <div>
            <span role='status'>{visible ? 'Saving' : 'Idle'}</span>
            {children}
        </div>
    ),
}));

const variable = (value: string, rules = 'required|string'): ServerStartupVariable => ({
    object: 'egg_variable',
    attributes: {
        name: 'Port',
        description: 'The port to listen on.',
        env_variable: 'PORT',
        default_value: '25565',
        server_value: value,
        is_editable: true,
        rules,
        options: [],
        has_options_source: false,
        allow_manual_input: true,
    },
    meta: { startup_command: `start ${value}`, raw_startup_command: 'start {{PORT}}' },
});
const images = { 'Java 21': 'ghcr.io/pterodactyl/yolks:java_21', 'Java 17': 'ghcr.io/pterodactyl/yolks:java_17' };
const originalAdapter = http.defaults.adapter;
const server = createTestServer();
let host: ExtensionTestHost | undefined;
let saves: { value: string; finish: () => void; fail: () => void }[];
let imageChanges: { image: string; finish: () => void }[];

beforeEach(() => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    vi.clearAllMocks();
    mocks.copy.mockResolvedValue(true);
    saves = [];
    imageChanges = [];
    http.defaults.adapter = ((config) =>
        new Promise((resolve, reject) => {
            const body = JSON.parse(String(config.data)) as { value?: string; docker_image?: string };
            const respond = (data: ServerStartupVariable | '') => () =>
                resolve({ config, headers: {}, status: 200, statusText: 'OK', data });

            if (body.docker_image !== undefined) {
                imageChanges.push({ image: body.docker_image, finish: respond('') });

                return;
            }

            const value = body.value ?? '';

            saves.push({ value, finish: respond(variable(value)), fail: () => reject(new Error('Save failed')) });
        })) satisfies AxiosAdapter;
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    http.defaults.adapter = originalAdapter;
    vi.useRealTimers();
});

function renderStartup(variables: ServerStartupVariable[] = [variable('original')], dockerImage = images['Java 21']) {
    host = createExtensionTestHost({
        server: { ...server, attributes: { ...server.attributes, docker_image: dockerImage } },
    });
    host.queryClient.setQueryData(serverStartupQueryOptions(server.attributes.uuid).queryKey, {
        object: 'list',
        data: variables,
        meta: { startup_command: 'start original', raw_startup_command: 'start {{PORT}}', docker_images: images },
    });

    return render(<View name='page.server.startup' />, { wrapper: host.Wrapper });
}

const advance = (ms = 1) =>
    act(async () => {
        await vi.advanceTimersByTimeAsync(ms);
    });
const variableStatus = () => screen.getAllByRole('status').at(-1)!;

describe('startup variables', () => {
    it('saves the edited value as soon as the field loses focus', async () => {
        renderStartup();
        const input = await screen.findByRole('textbox');

        fireEvent.change(input, { target: { value: '25566' } });
        fireEvent.blur(input);
        await advance();
        expect(saves.map((save) => save.value)).toEqual(['25566']);

        await advance(500);
        expect(saves).toHaveLength(1);
    });

    it('serializes saves and keeps the newest draft while earlier saves finish', async () => {
        renderStartup();
        const input = await screen.findByRole('textbox');

        fireEvent.change(input, { target: { value: 'first' } });
        await advance(500);
        expect(saves).toHaveLength(1);
        fireEvent.change(input, { target: { value: 'second' } });
        await advance(500);
        expect(saves).toHaveLength(1);
        expect(input).toHaveValue('second');
        expect(variableStatus()).toHaveTextContent('Saving');

        saves[0]!.finish();
        await advance();
        expect(saves.map((save) => save.value)).toEqual(['first', 'second']);
        expect(input).toHaveValue('second');
        expect(variableStatus()).toHaveTextContent('Saving');
        saves[1]!.finish();
        await advance();
        expect(input).toHaveValue('second');
        expect(variableStatus()).toHaveTextContent('Idle');
        expect(screen.getByText('start second')).toBeVisible();
    });

    it('keeps a failed draft and saves edits made without a keyboard event', async () => {
        renderStartup();
        const input = await screen.findByRole('textbox');

        fireEvent.input(input, { target: { value: 'pasted' } });
        await advance(500);
        expect(saves[0]?.value).toBe('pasted');
        saves[0]!.fail();
        await advance();
        expect(input).toHaveValue('pasted');
        expect(variableStatus()).toHaveTextContent('Idle');
        expect(mocks.error).toHaveBeenCalledOnce();
        fireEvent.input(input, { target: { value: 'corrected' } });
        await advance(500);
        saves[1]!.finish();
        await advance();
        expect(input).toHaveValue('corrected');
    });

    it('saves an unsent edit when the page unmounts', async () => {
        const view = renderStartup();

        fireEvent.change(await screen.findByRole('textbox'), { target: { value: 'unsent' } });
        view.unmount();
        await advance();

        expect(saves.map((save) => save.value)).toEqual(['unsent']);
    });

    it('edits a boolean variable with a switch', async () => {
        renderStartup([variable('0', 'required|boolean')]);

        fireEvent.click(await screen.findByRole('switch'));
        await advance(500);

        expect(saves.map((save) => save.value)).toEqual(['1']);
    });

    it('offers remote suggestions and falls back to the default for unknown values', async () => {
        const remote = variable('1.21.11');
        remote.attributes.has_options_source = true;
        remote.attributes.options = [
            { label: 'Minecraft 1.21.11', value: '1.21.11' },
            { label: 'Minecraft 1.20.6', value: '1.20.6' },
        ];
        renderStartup([remote]);

        const input = (await screen.findAllByRole('combobox'))[1];
        fireEvent.click(screen.getAllByRole('button', { name: 'Open popup' })[1]);
        expect(await screen.findByText('Minecraft 1.21.11')).toBeVisible();

        fireEvent.focus(input);
        fireEvent.change(input, { target: { value: 'custom-build' } });
        fireEvent.blur(input);
        await advance();
        expect(saves.map((save) => save.value)).toEqual(['25565']);
    });
});

describe('startup command', () => {
    it('copies the rendered command', async () => {
        renderStartup();

        fireEvent.click(await screen.findByRole('button', { name: 'Copy startup command' }));
        await advance();

        expect(mocks.copy).toHaveBeenCalledWith('start original');
        expect(screen.getByText('Copied text to clipboard.')).toBeVisible();
    });
});

describe('startup docker image', () => {
    it('shows an image an administrator set as fixed', async () => {
        renderStartup(undefined, 'ghcr.io/example/custom:latest');

        expect(await screen.findByDisplayValue('ghcr.io/example/custom:latest')).toBeDisabled();
        expect(screen.getByText(/manually set by an administrator/)).toBeVisible();
    });

    it('shares an image change the startup form slot starts with the image picker', async () => {
        let slot: StartupFormSlotData | undefined;

        prepareExtensions([{ id: 'startup-form', entry: '/startup.js' }]);
        const batch = createExtensionRegistryBatch();

        registerSlotComponent(
            'startup-form',
            'server.startup.form',
            ({ data }: SlotComponentProps) => {
                slot = data as StartupFormSlotData;

                return <p>{slot.isPending ? 'Form pending' : 'Form idle'}</p>;
            },
            batch
        );
        commitExtensionRegistryBatch('startup-form', batch);
        renderStartup();

        expect(await screen.findByText('Form idle')).toBeVisible();
        expect(slot?.canChangeDockerImage).toBe(true);
        await expect(slot!.setDockerImage('ghcr.io/example/other')).rejects.toThrow('cannot be selected');

        void act(() => slot!.setDockerImage(images['Java 17']));
        await advance();
        expect(imageChanges.map((change) => change.image)).toEqual([images['Java 17']]);
        expect(screen.getByText('Form pending')).toBeVisible();
        expect(screen.getAllByRole('status')[0]).toHaveTextContent('Saving');

        imageChanges[0]!.finish();
        await advance();
        expect(screen.getByText('Form idle')).toBeVisible();
    });
});
