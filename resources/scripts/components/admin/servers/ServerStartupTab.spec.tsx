/** @vitest-environment jsdom */

import { act, cleanup, render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as EggQueries from '@/api/admin/eggs/queries';
import type * as ServerQueries from '@/api/admin/servers/queries';
import type { SelectProps } from '@/components/ui/Select';
import ServerStartupTab from './ServerStartupTab';

const mocks = vi.hoisted(() => ({ fetchEgg: vi.fn() }));

const variable = (id: number, env: string, defaultValue: string) => ({
    object: 'egg_variable',
    attributes: { id, name: env, description: '', env_variable: env, default_value: defaultValue, rules: '' },
});

const egg = (id: number, image: string, variables: ReturnType<typeof variable>[]) => ({
    object: 'egg',
    attributes: {
        id,
        name: `Egg ${id}`,
        startup: `start-${id}`,
        docker_images: { [`Image ${id}`]: image },
        relationships: { variables: { object: 'list', data: variables } },
    },
});

const eggs = new Map([
    [1, egg(1, 'img:one', [variable(1, 'ONE', 'one-default')])],
    [2, egg(2, 'img:two', [variable(2, 'TWO', 'two-default')])],
    [3, egg(3, 'img:three', [variable(3, 'THREE', 'three-default')])],
]);

const server = {
    attributes: {
        id: 9,
        egg: 1,
        container: { startup_command: 'start-1', image: 'img:custom', skip_scripts: false, installed: 1 },
        relationships: {
            variables: {
                object: 'list',
                data: [
                    {
                        object: 'server_variable',
                        attributes: { env_variable: 'ONE', server_value: 'one-server', default_value: 'one-default' },
                    },
                ],
            },
        },
    },
};

vi.mock('@/components/ui/Select', () => ({
    default: ({ id, value, options = [], groups = [], disabled, onChange, multiple }: SelectProps) => {
        const choices = [...options, ...groups.flatMap((group) => group.options)];

        return (
            <select
                data-testid={id}
                value={String(value ?? '')}
                disabled={disabled}
                onChange={(event) => {
                    const choice = choices.find((option) => String(option.value) === event.currentTarget.value);

                    if (choice && !multiple) {
                        onChange(choice.value);
                    }
                }}
            >
                {choices.map((option) => (
                    <option key={option.value} value={String(option.value)}>
                        {String(option.value)}
                    </option>
                ))}
            </select>
        );
    },
}));
vi.mock('@/components/admin/servers/useServerDetail', () => ({
    useServerDetail: () => ({ server, reload: vi.fn() }),
}));
vi.mock('@/api/admin/eggs/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof EggQueries>()),
    useAdminEggs: () => ({ data: { data: [...eggs.values()] } }),
}));
vi.mock('@/api/admin/servers/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof ServerQueries>()),
    useAdminEggForServer: (eggId: number) => ({ data: eggs.get(eggId), isFetching: false }),
    useFetchAdminEggForServer: () => mocks.fetchEgg,
    useUpdateAdminServerStartup: () => ({ mutateAsync: vi.fn() }),
}));

const deferred = <T,>() => {
    let resolve: (value: T) => void = () => {};

    const promise = new Promise<T>((settle) => {
        resolve = settle;
    });

    return { promise, resolve };
};

const environmentInput = (env: string) => document.getElementById(`environment_${env}`);

afterEach(cleanup);
beforeEach(() => {
    vi.clearAllMocks();
    mocks.fetchEgg.mockImplementation((eggId: number) => Promise.resolve(eggs.get(eggId)));
});

describe('ServerStartupTab', () => {
    it('ignores an egg that finishes loading after a newer egg was picked', async () => {
        const user = userEvent.setup();
        const slowEgg = deferred<ReturnType<typeof egg> | undefined>();

        mocks.fetchEgg.mockImplementation((eggId: number) =>
            eggId === 2 ? slowEgg.promise : Promise.resolve(eggs.get(eggId))
        );
        render(<ServerStartupTab />);

        await user.selectOptions(screen.getByTestId('eggId'), '2');
        await user.selectOptions(screen.getByTestId('eggId'), '3');
        await waitFor(() => expect(screen.getByTestId('image')).toHaveValue('img:three'));

        await act(async () => slowEgg.resolve(eggs.get(2)));

        expect(screen.getByTestId('eggId')).toHaveValue('3');
        expect(screen.getByTestId('image')).toHaveValue('img:three');
        expect(environmentInput('THREE')).toHaveValue('three-default');
    });

    it("restores the server's own image and variables when switching back to its egg", async () => {
        const user = userEvent.setup();

        render(<ServerStartupTab />);

        expect(screen.getByTestId('image')).toHaveValue('img:custom');

        await user.selectOptions(screen.getByTestId('eggId'), '2');
        await waitFor(() => expect(screen.getByTestId('image')).toHaveValue('img:two'));

        await user.selectOptions(screen.getByTestId('eggId'), '1');
        await waitFor(() => expect(screen.getByTestId('image')).toHaveValue('img:custom'));
        expect(environmentInput('ONE')).toHaveValue('one-server');
    });
});
