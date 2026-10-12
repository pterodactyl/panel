import type { ComponentProps, JSXElementConstructor, ReactElement } from 'react';
import { Copy, Pencil, Server, Trash2 } from 'lucide-react';
import {
    Alert,
    Avatar,
    Button,
    Checkbox,
    ContentBox,
    CopyOnClick,
    DropdownMenu,
    DropdownMenuItem,
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    Input,
    Label,
    NumberInput,
    PageHeading,
    Pagination,
    Select,
    Spinner,
    Switch,
    Table,
    TextArea,
    TitledGreyBox,
    Tooltip,
} from '@/sdk';

export interface UiPrimitive<C extends JSXElementConstructor<never> = JSXElementConstructor<never>> {
    readonly component: C;
    /** Rendered by the workbench's UI kit, by variant; the first is the default. */
    readonly samples: Readonly<Record<string, ReactElement>>;
}

/** The component of each UI kit primitive by name; a new primitive adds itself here and to `uiPrimitives`. */
export interface UiPrimitiveComponents {
    Button: typeof Button;
    Input: typeof Input;
    TextArea: typeof TextArea;
    NumberInput: typeof NumberInput;
    Select: typeof Select;
    Checkbox: typeof Checkbox;
    Switch: typeof Switch;
    Label: typeof Label;
    Alert: typeof Alert;
    Tooltip: typeof Tooltip;
    DropdownMenu: typeof DropdownMenu;
    Table: typeof Table;
    Pagination: typeof Pagination;
    Empty: typeof Empty;
    Spinner: typeof Spinner;
    Avatar: typeof Avatar;
    ContentBox: typeof ContentBox;
    TitledGreyBox: typeof TitledGreyBox;
    CopyOnClick: typeof CopyOnClick;
    PageHeading: typeof PageHeading;
}

const noop = () => {};

const rows = [
    { id: 'survival', name: 'Survival', memory: '4 GiB' },
    { id: 'creative', name: 'Creative', memory: '2 GiB' },
];
const columns = [
    { id: 'name', label: 'Name', cell: (row: (typeof rows)[number]) => row.name },
    { id: 'memory', label: 'Memory', cell: (row: (typeof rows)[number]) => row.memory },
];
const page = (current: number, total: number) => ({
    data: rows,
    meta: { pagination: { total: total * 2, count: 2, per_page: 2, current_page: current, total_pages: total } },
});
const options = [
    { value: 'paper', label: 'Paper' },
    { value: 'vanilla', label: 'Vanilla' },
];

/**
 * Every UI kit primitive with sample renders: the workbench's gallery and `renderPrimitive` read
 * this list, so a new primitive is added here with its variants.
 */
export type UiPrimitives = { readonly [N in keyof UiPrimitiveComponents]: UiPrimitive<UiPrimitiveComponents[N]> };

export const uiPrimitives: UiPrimitives = {
    Button: {
        component: Button,
        samples: {
            primary: <Button>Save changes</Button>,
            secondary: <Button isSecondary>Cancel</Button>,
            grey: <Button color='grey'>Details</Button>,
            danger: <Button color='red'>Delete</Button>,
            small: <Button size='xsmall'>Small</Button>,
            loading: <Button isLoading>Saving</Button>,
            disabled: <Button disabled>Unavailable</Button>,
        },
    },
    Input: {
        component: Input,
        samples: {
            default: <Input placeholder='Server name' />,
            filled: <Input defaultValue='Survival' />,
            error: <Input $hasError defaultValue='?' aria-invalid />,
            disabled: <Input disabled defaultValue='Locked' />,
        },
    },
    TextArea: {
        component: TextArea,
        samples: {
            default: <TextArea rows={3} placeholder='Description' />,
            filled: <TextArea rows={3} defaultValue={'A survival world\nwith friends.'} />,
        },
    },
    NumberInput: {
        component: NumberInput,
        samples: {
            default: <NumberInput value={1024} onValueChange={noop} aria-label='Memory' />,
            disabled: <NumberInput value={0} onValueChange={noop} disabled aria-label='Swap' />,
        },
    },
    Select: {
        component: Select,
        samples: {
            default: <Select value='paper' onChange={noop} options={options} />,
            placeholder: <Select value={null} onChange={noop} options={options} placeholder='Choose a server jar' />,
            disabled: <Select value='vanilla' onChange={noop} options={options} disabled />,
        },
    },
    Checkbox: {
        component: Checkbox,
        samples: {
            checked: <Checkbox checked onChange={noop} aria-label='Checked' />,
            unchecked: <Checkbox checked={false} onChange={noop} aria-label='Unchecked' />,
            indeterminate: <Checkbox checked={false} indeterminate onChange={noop} aria-label='Some' />,
            disabled: <Checkbox checked disabled onChange={noop} aria-label='Disabled' />,
        },
    },
    Switch: {
        component: Switch,
        samples: {
            on: <Switch checked onChange={noop} label='Start on boot' />,
            off: <Switch checked={false} onChange={noop} label='Start on boot' />,
            described: (
                <Switch
                    checked
                    onChange={noop}
                    label='Crash detection'
                    description='Restart the server after a crash.'
                />
            ),
            disabled: <Switch checked={false} disabled onChange={noop} label='Unavailable' />,
        },
    },
    Label: { component: Label, samples: { default: <Label>Server name</Label> } },
    Alert: {
        component: Alert,
        samples: {
            info: <Alert type='info'>Backups run every night at 03:00.</Alert>,
            success: (
                <Alert type='success' title='Saved'>
                    Your changes are live.
                </Alert>
            ),
            warning: <Alert type='warning'>This server is near its disk limit.</Alert>,
            danger: <Alert type='danger'>The node is unreachable.</Alert>,
        },
    },
    Tooltip: {
        component: Tooltip,
        samples: {
            default: (
                <Tooltip content='Copies the address'>
                    <Button color='grey'>Hover me</Button>
                </Tooltip>
            ),
        },
    },
    DropdownMenu: {
        component: DropdownMenu,
        samples: {
            default: (
                <DropdownMenu triggerContent='Actions'>
                    <DropdownMenuItem icon={Pencil}>Rename</DropdownMenuItem>
                    <DropdownMenuItem icon={Copy}>Duplicate</DropdownMenuItem>
                    <DropdownMenuItem icon={Trash2} danger>
                        Delete
                    </DropdownMenuItem>
                </DropdownMenu>
            ),
        },
    },
    Table: {
        component: Table,
        samples: {
            rows: <Table rows={rows} columns={columns} keyOf={(row) => row.id} emptyState='No servers.' />,
            empty: <Table rows={[]} columns={columns} keyOf={(row) => row.id} emptyState='No servers yet.' />,
        },
    },
    Pagination: {
        component: Pagination,
        samples: {
            first: (
                <Pagination data={page(1, 3)} onPageSelect={noop}>
                    {({ items }) => items.map((row) => <p key={row.id}>{row.name}</p>)}
                </Pagination>
            ),
            middle: (
                <Pagination data={page(2, 3)} onPageSelect={noop}>
                    {({ items }) => items.map((row) => <p key={row.id}>{row.name}</p>)}
                </Pagination>
            ),
        },
    },
    Empty: {
        component: Empty,
        samples: {
            default: (
                <Empty className='border'>
                    <EmptyHeader>
                        <EmptyMedia variant='icon'>
                            <Server />
                        </EmptyMedia>
                        <EmptyTitle>No servers yet</EmptyTitle>
                        <EmptyDescription>Servers you own or are invited to will appear here.</EmptyDescription>
                    </EmptyHeader>
                </Empty>
            ),
        },
    },
    Spinner: {
        component: Spinner,
        samples: {
            base: <Spinner />,
            small: <Spinner size='small' />,
            large: <Spinner size='large' />,
        },
    },
    Avatar: {
        component: Avatar,
        samples: {
            default: <Avatar name='11111111-1111-4111-8111-111111111111' size={40} />,
            square: <Avatar name='22222222-2222-4222-8222-222222222222' size={40} square />,
        },
    },
    ContentBox: {
        component: ContentBox,
        samples: {
            default: <ContentBox title='Resources'>Memory, disk and CPU limits.</ContentBox>,
            loading: (
                <ContentBox title='Resources' showLoadingOverlay>
                    Memory, disk and CPU limits.
                </ContentBox>
            ),
        },
    },
    TitledGreyBox: {
        component: TitledGreyBox,
        samples: {
            default: <TitledGreyBox title='SFTP details'>sftp://127.0.0.1:2022</TitledGreyBox>,
        },
    },
    CopyOnClick: {
        component: CopyOnClick,
        samples: {
            default: (
                <CopyOnClick text='127.0.0.1:25565'>
                    <span className='font-mono text-sm'>127.0.0.1:25565</span>
                </CopyOnClick>
            ),
        },
    },
    PageHeading: {
        component: PageHeading,
        samples: {
            default: <PageHeading title='Servers' description='Every server you own or are invited to.' />,
            actions: <PageHeading title='Backups' actions={<Button>Create backup</Button>} />,
        },
    },
};

export type UiPrimitiveName = keyof UiPrimitiveComponents;
export type UiPrimitiveProps<N extends UiPrimitiveName> = ComponentProps<UiPrimitiveComponents[N]>;

export function getUiPrimitive(name: string): UiPrimitive | undefined {
    return Object.entries(uiPrimitives).find(([key]) => key === name)?.[1];
}
