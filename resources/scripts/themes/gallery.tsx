import { useState, type ComponentProps, type JSXElementConstructor, type ReactElement } from 'react';
import { Archive, Copy, Cpu, Pencil, RefreshCw, Server, Trash2, Upload } from 'lucide-react';
import {
    Alert,
    Avatar,
    Badge,
    Button,
    Card,
    Checkbox,
    Code,
    ContentBox,
    CopyOnClick,
    Dialog,
    DropdownMenu,
    DropdownMenuItem,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    FileInput,
    Input,
    Label,
    ListRow,
    NewButton,
    NumberInput,
    PageHeading,
    Pagination,
    ProgressBar,
    Select,
    Spinner,
    SpinnerOverlay,
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
    NewButton: typeof NewButton;
    Input: typeof Input;
    TextArea: typeof TextArea;
    NumberInput: typeof NumberInput;
    FileInput: typeof FileInput;
    Select: typeof Select;
    Checkbox: typeof Checkbox;
    Switch: typeof Switch;
    Label: typeof Label;
    Card: typeof Card;
    ListRow: typeof ListRow;
    PageHeading: typeof PageHeading;
    Badge: typeof Badge;
    Alert: typeof Alert;
    ProgressBar: typeof ProgressBar;
    Dialog: typeof Dialog;
    Tooltip: typeof Tooltip;
    DropdownMenu: typeof DropdownMenu;
    Table: typeof Table;
    Pagination: typeof Pagination;
    Empty: typeof Empty;
    Spinner: typeof Spinner;
    SpinnerOverlay: typeof SpinnerOverlay;
    Avatar: typeof Avatar;
    Code: typeof Code;
    CopyOnClick: typeof CopyOnClick;
    ContentBox: typeof ContentBox;
    TitledGreyBox: typeof TitledGreyBox;
}

const noop = () => {};

/** A dialog the gallery can close and reopen; `open` samples start open, so the frame shows them at once. */
function DialogSample({
    open: initiallyOpen,
    danger,
}: {
    open: boolean;
    danger?: boolean;
}): ReactElement {
    const [open, setOpen] = useState(initiallyOpen);

    return (
        <>
            <Button color={danger ? 'red' : 'primary'} onClick={() => setOpen(true)}>
                {danger ? 'Delete server' : 'Rename server'}
            </Button>
            {danger ? (
                <Dialog.Confirm
                    open={open}
                    onClose={() => setOpen(false)}
                    title='Delete this server?'
                    confirm='Delete'
                    onConfirmed={() => setOpen(false)}
                >
                    Survival and its files are removed for good.
                </Dialog.Confirm>
            ) : (
                <Dialog
                    open={open}
                    onClose={() => setOpen(false)}
                    title='Rename server'
                    description='The new name shows on the dashboard and in the console.'
                >
                    <Input defaultValue='Survival' aria-label='Server name' />
                    <Dialog.Footer>
                        <Button.Text onClick={() => setOpen(false)}>Cancel</Button.Text>
                        <Button onClick={() => setOpen(false)}>Save</Button>
                    </Dialog.Footer>
                </Dialog>
            )}
        </>
    );
}

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
            green: <Button color='green'>Start</Button>,
            orange: <Button color='orange'>Restart</Button>,
            danger: <Button color='red'>Delete</Button>,
            secondaryDanger: (
                <Button color='red' isSecondary>
                    Kill
                </Button>
            ),
            text: <Button.Text>Restart</Button.Text>,
            xsmall: <Button size='xsmall'>Extra small</Button>,
            small: <Button size='small'>Small</Button>,
            large: <Button size='large'>Large</Button>,
            xlarge: <Button size='xlarge'>Full width</Button>,
            loading: <Button isLoading>Saving</Button>,
            disabled: <Button disabled>Unavailable</Button>,
        },
    },
    NewButton: {
        component: NewButton,
        samples: {
            default: <NewButton>New database</NewButton>,
            icon: <NewButton icon={Upload}>Upload</NewButton>,
        },
    },
    Input: {
        component: Input,
        samples: {
            default: <Input placeholder='Server name' />,
            filled: <Input defaultValue='Survival' />,
            light: <Input $isLight defaultValue='On a card' />,
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
    FileInput: {
        component: FileInput,
        samples: {
            default: <FileInput aria-label='Server icon' />,
            disabled: <FileInput disabled aria-label='Server icon' />,
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
    Label: {
        component: Label,
        samples: { default: <Label>Server name</Label>, light: <Label isLight>Server name</Label> },
    },
    Card: {
        component: Card,
        samples: {
            plain: <Card>Memory, disk and CPU limits.</Card>,
            titled: <Card title='Resources'>Memory, disk and CPU limits.</Card>,
            icon: (
                <Card title='CPU' icon={Cpu}>
                    37.52% of 100%
                </Card>
            ),
            actions: (
                <Card
                    title='Backups'
                    actions={
                        <Button size='xsmall' color='grey'>
                            Refresh
                        </Button>
                    }
                >
                    2 of 5 backups used.
                </Card>
            ),
            loading: (
                <Card title='Resources' loading>
                    Memory, disk and CPU limits.
                </Card>
            ),
        },
    },
    ListRow: {
        component: ListRow,
        samples: {
            default: <ListRow>Survival · 203.0.113.10:25565</ListRow>,
            interactive: (
                <ListRow interactive onClick={noop}>
                    Creative · 203.0.113.10:25566
                </ListRow>
            ),
            link: <ListRow destination={{ to: '/' }}>Dashboard</ListRow>,
            list: (
                <ul className='space-y-2'>
                    <ListRow as='li'>world</ListRow>
                    <ListRow as='li'>world_nether</ListRow>
                </ul>
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
    Badge: {
        component: Badge,
        samples: {
            neutral: <Badge>Installing</Badge>,
            success: <Badge tone='success'>Running</Badge>,
            warning: <Badge tone='warning'>Starting</Badge>,
            danger: <Badge tone='danger'>Suspended</Badge>,
            info: <Badge tone='info'>Update</Badge>,
            accent: <Badge tone='accent'>New</Badge>,
            soft: (
                <span className='flex flex-wrap gap-2'>
                    {(['neutral', 'success', 'warning', 'danger', 'info', 'accent'] as const).map((tone) => (
                        <Badge key={tone} tone={tone} variant='soft'>
                            {tone}
                        </Badge>
                    ))}
                </span>
            ),
            small: (
                <Badge tone='accent' size='sm'>
                    Beta
                </Badge>
            ),
        },
    },
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
            danger: (
                <Alert type='danger' title='Node offline'>
                    The node is unreachable.
                </Alert>
            ),
        },
    },
    ProgressBar: {
        component: ProgressBar,
        samples: {
            neutral: <ProgressBar value={40} label='Disk' />,
            success: <ProgressBar value={25} tone='success' label='Memory' />,
            warning: <ProgressBar value={85} tone='warning' label='Disk' />,
            danger: <ProgressBar value={96} tone='danger' label='CPU' />,
            accent: <ProgressBar value={60} tone='accent' label='Upload' />,
            empty: <ProgressBar value={null} label='Unlimited' />,
            page: <ProgressBar value={70} variant='page' />,
        },
    },
    Dialog: {
        component: Dialog,
        samples: {
            open: <DialogSample open />,
            confirm: <DialogSample open={false} danger />,
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
            described: (
                <DropdownMenu triggerContent='Backup'>
                    <DropdownMenuItem icon={RefreshCw} description='Replaces the files on the server.'>
                        Restore
                    </DropdownMenuItem>
                    <DropdownMenuItem icon={Archive} description='Keeps it through rotation.'>
                        Lock
                    </DropdownMenuItem>
                    <DropdownMenuItem icon={Trash2} danger description='This cannot be undone.'>
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
            content: (
                <Empty className='border'>
                    <EmptyHeader>
                        <EmptyTitle>No databases</EmptyTitle>
                        <EmptyDescription>This server has none yet.</EmptyDescription>
                    </EmptyHeader>
                    <EmptyContent>
                        <NewButton>New database</NewButton>
                    </EmptyContent>
                </Empty>
            ),
            compact: (
                <Empty size='compact' className='border'>
                    <EmptyHeader>
                        <EmptyTitle>No backups</EmptyTitle>
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
            blue: <Spinner isBlue />,
            centered: <Spinner centered />,
        },
    },
    SpinnerOverlay: {
        component: SpinnerOverlay,
        samples: {
            default: (
                <div className='relative h-32 rounded-sm border border-border'>
                    <SpinnerOverlay visible>Saving</SpinnerOverlay>
                </div>
            ),
        },
    },
    Avatar: {
        component: Avatar,
        samples: {
            default: <Avatar name='11111111-1111-4111-8111-111111111111' size={40} />,
            square: <Avatar name='22222222-2222-4222-8222-222222222222' size={40} square />,
            marble: <Avatar name='33333333-3333-4333-8333-333333333333' size={40} variant='marble' />,
            pixel: <Avatar name='44444444-4444-4444-8444-444444444444' size={40} variant='pixel' />,
        },
    },
    Code: {
        component: Code,
        samples: {
            default: <Code>server.properties</Code>,
            dark: <Code dark>java -Xms128M -jar server.jar</Code>,
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
};

export type UiPrimitiveName = keyof UiPrimitiveComponents;
export type UiPrimitiveProps<N extends UiPrimitiveName> = ComponentProps<UiPrimitiveComponents[N]>;

export function getUiPrimitive(name: string): UiPrimitive | undefined {
    return Object.entries(uiPrimitives).find(([key]) => key === name)?.[1];
}
