import { NavigationLabel, type SubNavigationItemState } from '@/sdk';
import type { NavItem } from '@/sdk/theme';

/** Classic's tabs name the panel's own pages in text; extension screens keep their icons. */
export const tabLabel = (item: NavItem, state: SubNavigationItemState) => (
    <NavigationLabel
        label={item.label}
        group={item.group}
        icon={item.extensionId === undefined ? null : item.icon}
        badge={state.badge}
    />
);
