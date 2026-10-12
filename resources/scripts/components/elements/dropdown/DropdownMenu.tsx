import React, { use } from 'react';
import { Menu } from '@base-ui/react/menu';
import { ContextMenu } from '@base-ui/react/context-menu';
import type { LucideIcon } from 'lucide-react';
import Icon from '@/components/elements/Icon';
import { PanelLink, type PanelDestination } from '@/sdk/navigation';
import { useSkin } from '@/themes/skins';

type MenuItemComponent = React.ComponentType<React.ComponentProps<typeof Menu.Item>>;
const MenuItemContext = React.createContext<MenuItemComponent>(Menu.Item as MenuItemComponent);
const ContextMenuItem = (props: React.ComponentProps<typeof Menu.Item>) => <ContextMenu.Item {...props} />;

export interface DropdownMenuProps {
    /** Rendered inside the trigger `<button>`. */
    triggerContent: React.ReactNode;
    triggerClassName?: string;
    openOnHover?: boolean;
    children: React.ReactNode;
    className?: string;
}

function DropdownMenu({ triggerContent, triggerClassName, openOnHover, children, className }: DropdownMenuProps) {
    const ui = useSkin('DropdownMenu', { context: false });

    return (
        <Menu.Root>
            <Menu.Trigger
                {...ui('trigger', triggerClassName)}
                openOnHover={openOnHover}
                closeDelay={openOnHover ? 150 : 0}
            >
                {triggerContent}
            </Menu.Trigger>
            <Menu.Portal>
                <Menu.Positioner {...ui('positioner')} sideOffset={4} align='end'>
                    <Menu.Popup {...ui('root', className)}>
                        <MenuItemContext.Provider value={Menu.Item as MenuItemComponent}>
                            {children}
                        </MenuItemContext.Provider>
                    </Menu.Popup>
                </Menu.Positioner>
            </Menu.Portal>
        </Menu.Root>
    );
}

export interface ContextDropdownMenuProps {
    className?: string;
    menuClassName?: string;
    children: React.ReactNode;
    items: React.ReactNode;
}

/** Opens `items` at the cursor on right-click or long-press over `children`. */
export function ContextDropdownMenu({ className, menuClassName, children, items }: ContextDropdownMenuProps) {
    const ui = useSkin('DropdownMenu', { context: true });

    return (
        <ContextMenu.Root>
            <ContextMenu.Trigger {...ui('trigger', className)}>{children}</ContextMenu.Trigger>
            <ContextMenu.Portal>
                <ContextMenu.Positioner {...ui('positioner')}>
                    <ContextMenu.Popup {...ui('root', menuClassName)}>
                        <MenuItemContext.Provider value={ContextMenuItem}>{items}</MenuItemContext.Provider>
                    </ContextMenu.Popup>
                </ContextMenu.Positioner>
            </ContextMenu.Portal>
        </ContextMenu.Root>
    );
}

export interface DropdownMenuItemProps extends Omit<React.ComponentProps<typeof Menu.Item>, 'className'> {
    className?: string;
    icon?: LucideIcon;
    danger?: boolean;
    description?: React.ReactNode;
    /** Makes the item a link to a panel page; following it closes the menu. */
    destination?: PanelDestination;
}

/** An item for either DropdownMenu or ContextDropdownMenu. */
export function DropdownMenuItem({
    icon,
    danger,
    description,
    destination,
    className,
    children,
    ...props
}: DropdownMenuItemProps) {
    const Item = use(MenuItemContext);
    const ui = useSkin('DropdownMenuItem', { danger: !!danger, described: !!description });
    const content = (
        <>
            {icon && (
                <span {...ui('icon')}>
                    <Icon icon={icon} {...ui('glyph')} />
                </span>
            )}
            <span {...ui('text')}>
                <span {...ui('label')}>{children}</span>
                {description && <span {...ui('description')}>{description}</span>}
            </span>
        </>
    );

    if (destination) {
        return (
            <Menu.LinkItem
                {...ui('root', className)}
                id={props.id}
                label={props.label}
                closeOnClick
                render={<PanelLink destination={destination} />}
            >
                {content}
            </Menu.LinkItem>
        );
    }

    return (
        <Item {...ui('root', className)} {...props}>
            {content}
        </Item>
    );
}

// Assigned as a static property; Object.assign would hide the component from Fast Refresh.
const DropdownMenuWithItems = DropdownMenu as typeof DropdownMenu & { Item: typeof DropdownMenuItem };

DropdownMenuWithItems.Item = DropdownMenuItem;

export default DropdownMenuWithItems;
