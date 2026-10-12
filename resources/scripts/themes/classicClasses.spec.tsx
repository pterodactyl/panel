/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { readFileSync, writeFileSync } from 'node:fs';
import type { ReactElement } from 'react';
import { createMemoryHistory, createRootRoute, createRouter, RouterContextProvider } from '@tanstack/react-router';
import { createColumnHelper, getCoreRowModel, useReactTable } from '@tanstack/react-table';
import { Database, Plus } from 'lucide-react';
import { afterEach, expect, it } from 'vitest';
import Button, { ButtonStyle, buttonClassName, LinkButton, RouterLinkButton } from '@/components/elements/Button';
import { NewButton, NewLinkButton } from '@/components/elements/NewButton';
import { NumberInput, TextArea, TextInput } from '@/components/form/controls';
import Select from '@/components/ui/Select';
import Checkbox from '@/components/ui/Checkbox';
import Switch from '@/components/ui/Switch';
import Label from '@/components/elements/Label';
import { Dialog } from '@/components/elements/dialog';
import { Alert } from '@/components/elements/alert';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import DropdownMenu, { ContextDropdownMenu, DropdownMenuItem } from '@/components/elements/dropdown/DropdownMenu';
import DataTable from '@/components/elements/table/DataTable';
import Pagination from '@/components/elements/Pagination';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import Spinner from '@/components/elements/Spinner';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Avatar from '@/components/Avatar';
import ProgressBar from '@/components/elements/ProgressBar';
import Code from '@/components/elements/Code';
import CopyOnClick from '@/components/elements/CopyOnClick';
import ContentBox from '@/components/elements/ContentBox';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import GreyRowBox from '@/components/elements/GreyRowBox';
import PageHeading from '@/components/elements/PageHeading';
import { setHttpProgress } from '@/state/httpProgress';
import { createTestServerCard, renderView } from '@/sdk/testing';

/*
 * Every element's classes for each primitive variant, captured from the components before they read
 * classic's skins. Run with CAPTURE_CLASSIC_CLASSES=1 to record; otherwise the output must match.
 */
const fixture = 'resources/scripts/themes/__fixtures__/classic-classes.json';
const capture = process.env.CAPTURE_CLASSIC_CLASSES === '1';

// Arbitrary state variants replaced by their plain Tailwind equivalents; each pair compiles to the same selector.
const plainVariants: [RegExp, string][] = [
    [/^\[&:(hover|active):not\(:disabled\)\]:/, '$1:not-disabled:'],
    [/^(group-)?data-\[([a-z-]+)\]:/, '$1data-$2:'],
    [/-\[var\((--[a-z-]+)\)\]$/, '-($1)'],
    [/^z-\[(\d+)\]$/, 'z-$1'],
];

const plain = (token: string): string =>
    plainVariants.reduce((value, [pattern, replacement]) => value.replace(pattern, replacement), token);

/** Each element as `tag: classes`, the classes deduplicated and sorted since their order has no effect. */
function classes(): string[] {
    return [...document.body.querySelectorAll('[class]')].map((element) => {
        const tokens = new Set((element.getAttribute('class') ?? '').split(/\s+/).filter(Boolean));

        return `${element.tagName.toLowerCase()}: ${[...tokens].sort().join(' ')}`;
    });
}

const normalise = (captured: Record<string, string[]>) =>
    Object.fromEntries(
        Object.entries(captured).map(([name, elements]) => [
            name,
            elements.map((element) => {
                const [tag, tokens] = element.split(': ');

                return `${tag}: ${[...new Set(tokens.split(' ').map(plain))].sort().join(' ')}`;
            }),
        ])
    );

const router = createRouter({
    routeTree: createRootRoute(),
    history: createMemoryHistory({ initialEntries: ['/'] }),
});

const withRouter = (element: ReactElement) => <RouterContextProvider router={router}>{element}</RouterContextProvider>;

type Case = () => ReactElement | Promise<void>;
const noop = () => {};

const cases: Record<string, Case> = {};

const buttonColors = [undefined, 'primary', 'grey', 'green', 'red', 'orange'] as const;
const buttonSizes = [undefined, 'xsmall', 'small', 'large', 'xlarge'] as const;

for (const color of buttonColors) {
    for (const size of buttonSizes) {
        for (const isSecondary of [false, true]) {
            cases[`Button ${color ?? '-'} ${size ?? '-'} ${isSecondary ? 'secondary' : 'solid'}`] = () => (
                <Button color={color} size={size} isSecondary={isSecondary}>
                    Save
                </Button>
            );
        }
    }
}
Object.assign(cases, {
    'Button loading': () => <Button isLoading>Save</Button>,
    'Button disabled className': () => (
        <Button disabled className='w-full mt-4'>
            Save
        </Button>
    ),
    'Button.Text': () => <Button.Text size='xsmall'>Cancel</Button.Text>,
    'Button.Danger secondary': () => <Button.Danger isSecondary>Delete</Button.Danger>,
    'LinkButton': () => (
        <LinkButton href='/' color='green' className='ml-2'>
            Open
        </LinkButton>
    ),
    'RouterLinkButton': () => withRouter(<RouterLinkButton to='/'>Home</RouterLinkButton>),
    'ButtonStyle as span': () => (
        <ButtonStyle as='span' isSecondary size='large'>
            Label
        </ButtonStyle>
    ),
    'buttonClassName': () => <span className={buttonClassName({ color: 'red', size: 'xsmall', isSecondary: true })} />,
    'NewButton': () => <NewButton>New node</NewButton>,
    'NewButton icon className': () => (
        <NewButton icon={Database} isSecondary className='w-full'>
            New database
        </NewButton>
    ),
    'NewLinkButton': () => withRouter(<NewLinkButton to='/'>New server</NewLinkButton>),
    'Input': () => <TextInput defaultValue='a' />,
    'Input light': () => <TextInput $isLight />,
    'Input error': () => <TextInput $hasError className='pl-8' />,
    'Input light error disabled': () => <TextInput $isLight $hasError disabled />,
    'TextArea': () => <TextArea rows={3} />,
    'TextArea error': () => <TextArea $hasError $isLight />,
    'NumberInput': () => <NumberInput value={5} onValueChange={noop} />,
    'NumberInput error': () => <NumberInput value={5} onValueChange={noop} $hasError className='w-24' />,
    'Select': () => <Select value={1} onChange={noop} options={[{ value: 1, label: 'Alpha' }]} />,
    'Select error placeholder': () => (
        <Select value={null} onChange={noop} hasError placeholder='Pick' options={[{ value: 1, label: 'Alpha' }]} />
    ),
    'Select rich label': () => (
        <Select value={1} onChange={noop} options={[{ value: 1, label: <strong>Alpha</strong>, textLabel: 'Alpha' }]} />
    ),
    'Select multiple': () => (
        <Select
            multiple
            value={[1, 2]}
            onChange={noop}
            options={[
                { value: 1, label: 'Alpha' },
                { value: 2, label: 'Beta' },
            ]}
        />
    ),
    'Select open groups': async () => {
        render(
            <Select
                value={1}
                onChange={noop}
                groups={[
                    { label: 'Group', options: [{ value: 1, label: 'Alpha' }] },
                    { label: 'Other', options: [{ value: 2, label: 'Beta', disabled: true }] },
                ]}
            />
        );
        await userEvent.click(screen.getByRole('button', { name: 'Open popup' }));
        await screen.findByRole('option', { name: 'Beta' });
    },
    'Select open multiple': async () => {
        render(
            <Select
                multiple
                value={[1]}
                onChange={noop}
                options={[
                    { value: 1, label: 'Alpha' },
                    { value: 2, label: 'Beta' },
                ]}
            />
        );
        await userEvent.click(screen.getByRole('button', { name: 'Open popup' }));
        await screen.findByRole('option', { name: 'Beta' });
    },
    'Checkbox': () => <Checkbox checked={false} onChange={noop} />,
    'Checkbox checked': () => <Checkbox checked onChange={noop} className='mr-2' />,
    'Checkbox indeterminate disabled': () => <Checkbox checked={false} indeterminate disabled onChange={noop} />,
    'Switch': () => <Switch checked={false} onChange={noop} aria-label='Toggle' />,
    'Switch checked label': () => <Switch checked onChange={noop} label='Enabled' className='mt-2' />,
    'Switch description end disabled': () => (
        <Switch
            checked={false}
            onChange={noop}
            label='Enabled'
            description='Turns it on.'
            controlPosition='end'
            disabled
        />
    ),
    'Label': () => <Label>Name</Label>,
    'Label light span': () => (
        <Label as='span' isLight className='mb-0'>
            Name
        </Label>
    ),
    'Dialog': () => (
        <Dialog open onClose={noop} title='Delete server' description='This cannot be undone.'>
            <p>Body</p>
            <Dialog.Footer>
                <Button>Okay</Button>
            </Dialog.Footer>
        </Dialog>
    ),
    'Dialog title only': () => (
        <Dialog open onClose={noop} title='Rename'>
            <p>Body</p>
        </Dialog>
    ),
    'Dialog no close icon': () => (
        <Dialog open onClose={noop} hideCloseIcon>
            <p>Body</p>
        </Dialog>
    ),
    'Dialog icon title': () => (
        <Dialog open onClose={noop} title='Warning'>
            <Dialog.Icon type='warning' />
            <p>Body</p>
        </Dialog>
    ),
    'Dialog icon container': () => (
        <Dialog open onClose={noop} title='Danger'>
            <Dialog.Icon type='danger' position='container' className='mt-1' />
            <p>Body</p>
        </Dialog>
    ),
    'Dialog icon success info': () => (
        <>
            <Dialog open onClose={noop} title='Done'>
                <Dialog.Icon type='success' />
            </Dialog>
            <Dialog open onClose={noop} title='Note'>
                <Dialog.Icon type='info' />
            </Dialog>
        </>
    ),
    'Dialog.Confirm': () => (
        <Dialog.Confirm open onClose={noop} title='Delete?' confirm='Delete' onConfirmed={noop}>
            Sure?
        </Dialog.Confirm>
    ),
    'Dialog.Confirm pending': () => (
        <Dialog.Confirm open onClose={noop} title='Delete?' pending onConfirmed={noop}>
            Sure?
        </Dialog.Confirm>
    ),
    'Alert success': () => <Alert type='success'>Saved.</Alert>,
    'Alert info title': () => (
        <Alert type='info' title='Heads up' className='mb-4'>
            Note.
        </Alert>
    ),
    'Alert warning': () => <Alert type='warning'>Careful.</Alert>,
    'Alert danger': () => <Alert type='danger'>Failed.</Alert>,
    'Tooltip open': async () => {
        render(
            <Tooltip content='Help' className='max-w-xs'>
                <button type='button'>Target</button>
            </Tooltip>
        );
        screen.getByRole('button', { name: 'Target' }).focus();
        await screen.findByRole('tooltip');
    },
    'Tooltip open arrow': async () => {
        render(
            <Tooltip content='Help' arrow placement='bottom'>
                <button type='button'>Target</button>
            </Tooltip>
        );
        screen.getByRole('button', { name: 'Target' }).focus();
        await screen.findByRole('tooltip');
    },
    'DropdownMenu closed': () => (
        <DropdownMenu triggerContent='Menu' triggerClassName='p-2'>
            <DropdownMenuItem>One</DropdownMenuItem>
        </DropdownMenu>
    ),
    'DropdownMenu open': async () => {
        render(
            <DropdownMenu triggerContent='Menu' className='min-w-64'>
                <DropdownMenuItem>Plain</DropdownMenuItem>
                <DropdownMenuItem icon={Plus}>With icon</DropdownMenuItem>
                <DropdownMenuItem icon={Plus} description='Explains it'>
                    Described
                </DropdownMenuItem>
                <DropdownMenuItem icon={Plus} danger description='Gone forever' className='mt-1'>
                    Delete
                </DropdownMenuItem>
                <DropdownMenuItem danger>Danger plain</DropdownMenuItem>
            </DropdownMenu>
        );
        await userEvent.click(screen.getByRole('button', { name: 'Menu' }));
        await screen.findByRole('menuitem', { name: 'Plain' });
    },
    'ContextDropdownMenu open': async () => {
        render(
            <ContextDropdownMenu
                className='block'
                menuClassName='min-w-40'
                items={<DropdownMenuItem icon={Plus}>Rename</DropdownMenuItem>}
            >
                <span>Area</span>
            </ContextDropdownMenu>
        );
        fireEvent.contextMenu(screen.getByText('Area'));
        await screen.findByRole('menuitem', { name: 'Rename' });
    },
    'DataTable rows': () => <TableCase rows={[{ id: 'a', name: 'Alpha' }]} />,
    'DataTable fetching': () => <TableCase rows={[{ id: 'a', name: 'Alpha' }]} isFetching className='mt-4' />,
    'DataTable empty string': () => <TableCase rows={[]} />,
    'DataTable empty fetching node': () => <TableCase rows={[]} isFetching emptyState={<p className='p-4'>None</p>} />,
    'Pagination': () => (
        <Pagination data={paginated(3, 10)} onPageSelect={noop}>
            {({ items }) => <span className='items'>{items.length}</span>}
        </Pagination>
    ),
    'Pagination single': () => (
        <Pagination data={paginated(1, 1)} onPageSelect={noop}>
            {() => null}
        </Pagination>
    ),
    'Empty': () => (
        <Empty className='border bg-card'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>
                    <Database />
                </EmptyMedia>
                <EmptyTitle>Nothing</EmptyTitle>
                <EmptyDescription>
                    None yet, <a href='/'>add one</a>.
                </EmptyDescription>
            </EmptyHeader>
            <EmptyContent>
                <button type='button'>Add</button>
            </EmptyContent>
        </Empty>
    ),
    'Empty compact': () => (
        <Empty className={emptyCompactClass}>
            <EmptyMedia>
                <Database />
            </EmptyMedia>
            <EmptyDescription className='mt-2'>None</EmptyDescription>
        </Empty>
    ),
    'Spinner': () => <Spinner />,
    'Spinner small blue': () => <Spinner size='small' isBlue />,
    'Spinner large centered': () => <Spinner size='large' centered />,
    'Spinner centered': () => <Spinner centered />,
    'SpinnerOverlay': () => <SpinnerOverlay visible />,
    'SpinnerOverlay fixed large text': () => (
        <SpinnerOverlay visible fixed size='large' backgroundOpacity={0.5}>
            Loading
        </SpinnerOverlay>
    ),
    'Avatar': () => <Avatar name='uuid' size={32} />,
    'ProgressBar': () => {
        setHttpProgress(40);

        return <ProgressBar />;
    },
    'ProgressBar idle': () => {
        setHttpProgress(undefined);

        return <ProgressBar />;
    },
    'Code': () => <Code>value</Code>,
    'Code dark className': () => (
        <Code dark className='text-xs'>
            value
        </Code>
    ),
    'CopyOnClick': () => (
        <CopyOnClick text='abc'>
            <span className='font-mono'>abc</span>
        </CopyOnClick>
    ),
    'CopyOnClick status': async () => {
        document.body.appendChild(Object.assign(document.createElement('div'), { id: 'modal-portal' }));
        render(
            <CopyOnClick text='abc'>
                <span>abc</span>
            </CopyOnClick>
        );
        fireEvent.click(screen.getByText('abc'));
        await screen.findByRole('status');
    },
    'ContentBox': () => <ContentBox title='Account'>Body</ContentBox>,
    'ContentBox plain loading': () => (
        <ContentBox className='mt-4' borderColor='border-primary' showLoadingOverlay>
            Body
        </ContentBox>
    ),
    'TitledGreyBox': () => <TitledGreyBox title='Details'>Body</TitledGreyBox>,
    'TitledGreyBox icon classes': () => (
        <TitledGreyBox title='Details' icon={Database} className='mb-4' contentClassName='p-0'>
            Body
        </TitledGreyBox>
    ),
    'TitledGreyBox node title': () => (
        <TitledGreyBox title={<span className='flex'>Custom</span>}>Body</TitledGreyBox>
    ),
    'GreyRowBox': () => <GreyRowBox>Row</GreyRowBox>,
    'GreyRowBox static link': () => (
        <GreyRowBox as='a' href='/' $hoverable={false} className='mt-2'>
            Row
        </GreyRowBox>
    ),
    'PageHeading': () => <PageHeading title='Servers' />,
    'PageHeading full': () => (
        <PageHeading
            title='Servers'
            description='All of them.'
            actions={<button type='button'>New</button>}
            className='mb-4'
        />
    ),
});

for (const fixture of ['servers', 'empty', 'loading']) {
    cases[`view dashboard.serverList ${fixture}`] = async () => {
        renderView('dashboard.serverList', { fixture });
        await Promise.resolve();
    };
}
for (const fixture of ['running', 'offline', 'suspended', 'loading']) {
    cases[`view dashboard.serverCard ${fixture}`] = async () => {
        renderView('dashboard.serverCard', { fixture });
        await Promise.resolve();
    };
}
for (const reason of [
    'connection-error',
    'maintenance',
    'transferring',
    'installing',
    'restoring-backup',
    'unavailable',
] as const) {
    cases[`view dashboard.serverCard ${reason}`] = async () => {
        renderView('dashboard.serverCard', { model: createTestServerCard({ state: { kind: 'unavailable', reason } }) });
        await Promise.resolve();
    };
}
for (const fixture of ['user', 'admin']) {
    cases[`view layout.app ${fixture}`] = async () => {
        renderView('layout.app', { fixture });
        await Promise.resolve();
    };
}

interface Row {
    id: string;
    name: string;
}
const column = createColumnHelper<Row>();
const columns = [
    column.accessor('name', { header: 'Name', meta: { cellClassName: 'font-medium', headerClassName: 'w-1/2' } }),
    column.display({ id: 'actions', header: () => null, cell: () => <button type='button'>Edit</button> }),
];

function TableCase({
    rows,
    isFetching,
    className,
    emptyState = 'No rows',
}: {
    rows: Row[];
    isFetching?: boolean;
    className?: string;
    emptyState?: ReactElement | string;
}) {
    const table = useReactTable({ data: rows, columns, getRowId: (row) => row.id, getCoreRowModel: getCoreRowModel() });

    return <DataTable table={table} emptyState={emptyState} isFetching={isFetching} className={className} />;
}

function paginated(page: number, pages: number) {
    return {
        data: [1, 2],
        meta: { pagination: { total: pages * 2, count: 2, per_page: 2, current_page: page, total_pages: pages } },
    };
}

afterEach(cleanup);

it('renders classic primitives with the classes they had before skins', async () => {
    const captured: Record<string, string[]> = {};

    for (const [name, run] of Object.entries(cases)) {
        const element = await run();

        if (element) {
            render(element);
        }

        // Let effects that place parts (dialog icons, overlays) settle.
        await act(async () => {});
        captured[name] = classes();
        cleanup();
        document.body.innerHTML = '';
    }

    if (capture) {
        writeFileSync(fixture, `${JSON.stringify(captured, null, 4)}\n`);

        return;
    }

    expect(normalise(captured)).toEqual(normalise(JSON.parse(readFileSync(fixture, 'utf8'))));
});
