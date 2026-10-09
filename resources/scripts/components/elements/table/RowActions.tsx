import type { ComponentProps, ReactElement, ReactNode } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { Link, type AnyRouter, type LinkComponentProps, type RegisteredRouter } from '@tanstack/react-router';
import { MoreHorizontal, Pencil, Trash2, type LucideIcon } from 'lucide-react';
import Button, { buttonClassName } from '@/components/elements/Button';
import DropdownMenu from '@/components/elements/dropdown/DropdownMenu';
import Icon from '@/components/elements/Icon';
import Tooltip from '@/components/elements/tooltip/Tooltip';

const iconClassName = 'h-3.5 w-3.5';
const actionClassName = buttonClassName({ color: 'grey', size: 'xsmall', isSecondary: true });

type RowActionCount = 1 | 2 | 3;

const actionWidths = { 1: 'w-14', 2: 'w-24', 3: 'w-32' } satisfies Record<RowActionCount, string>;

/** The trailing, always-visible "Actions" column sized for `actions` icon buttons; `width` overrides the size. */
export function actionsColumn<TData>(
    actions: RowActionCount,
    cell: (row: TData) => ReactNode,
    width: string = actionWidths[actions]
): ColumnDef<TData> {
    const className = `${width} text-right`;

    return {
        id: 'actions',
        header: () => <span className='sr-only'>Actions</span>,
        cell: ({ row }) => cell(row.original),
        enableSorting: false,
        meta: { headerClassName: className, cellClassName: className },
    };
}

/** Lays out a row's actions: edit, delete, then any overflow menu. */
export function RowActions({ children }: { children: ReactNode }) {
    return <div className='flex items-center justify-end gap-1'>{children}</div>;
}

const ActionTooltip = ({ content, children }: { content: string; children: ReactElement }) => (
    <Tooltip content={content}>
        <span className='inline-flex'>{children}</span>
    </Tooltip>
);

interface RowActionButtonProps extends Omit<ComponentProps<'button'>, 'children' | 'color' | 'aria-label'> {
    'aria-label': string;
    icon: LucideIcon;
    /** Tooltip text; `disabledReason` replaces it while the button is disabled. */
    label: string;
    danger?: boolean;
    disabledReason?: string;
}

type PresetActionProps = Omit<RowActionButtonProps, 'icon' | 'label' | 'danger'>;

export function RowActionButton({ icon, label, danger, disabled, disabledReason, ...props }: RowActionButtonProps) {
    return (
        <ActionTooltip content={disabled && disabledReason ? disabledReason : label}>
            <Button.Text
                type='button'
                size='xsmall'
                color={danger ? 'red' : 'grey'}
                isSecondary
                disabled={disabled}
                {...props}
            >
                <Icon icon={icon} className={iconClassName} />
            </Button.Text>
        </ActionTooltip>
    );
}

export const EditAction = (props: PresetActionProps) => <RowActionButton icon={Pencil} label='Edit' {...props} />;

export const DeleteAction = ({ label = 'Delete', ...props }: PresetActionProps & { label?: string }) => (
    <RowActionButton icon={Trash2} label={label} danger {...props} />
);

/** `EditAction` for resources edited on another page. */
export function EditLinkAction<
    TRouter extends AnyRouter = RegisteredRouter,
    const TFrom extends string = string,
    const TTo extends string | undefined = undefined,
    const TMaskFrom extends string = TFrom,
    const TMaskTo extends string = '',
>(props: LinkComponentProps<'a', TRouter, TFrom, TTo, TMaskFrom, TMaskTo> & { 'aria-label': string }) {
    return (
        <ActionTooltip content='Edit'>
            <Link {...props} className={actionClassName}>
                <Icon icon={Pencil} className={iconClassName} />
            </Link>
        </ActionTooltip>
    );
}

/** The "…" menu for actions other than edit and delete. */
export function RowActionsMenu({ label, children }: { label: string; children: ReactNode }) {
    return (
        <DropdownMenu
            triggerClassName={actionClassName}
            triggerContent={
                <>
                    <Icon icon={MoreHorizontal} className={iconClassName} />
                    <span className='sr-only'>{label}</span>
                </>
            }
        >
            {children}
        </DropdownMenu>
    );
}
