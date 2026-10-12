import { ExternalLink } from 'lucide-react';
import { Icon, SubNavigation, SubNavigationLayout } from '@/sdk';
import { Main, Slot, type NavItem, type ViewProps } from '@/sdk/theme';
import { tabLabel } from './tabLabel';

/** An icon in the row; it is named once the navigation collapses or moves it into the menu. */
function AdminLinkLabel({ placement }: { placement: 'row' | 'menu' }) {
    return (
        <span className='inline-flex items-center gap-2 align-top' title='Open server administration'>
            <Icon icon={ExternalLink} aria-hidden />
            <span className={placement === 'row' ? 'sr-only in-data-collapsed:not-sr-only' : undefined}>
                Open Admin Area
            </span>
        </span>
    );
}

export default function ServerLayout({ model, children }: ViewProps<'layout.server'>) {
    const { adminLink } = model;
    const items: readonly NavItem[] = adminLink ? [...model.nav.items, adminLink] : model.nav.items;

    return (
        <SubNavigationLayout
            navigation={
                <SubNavigation
                    items={items}
                    canOverflow={(item) => item.extensionId !== undefined || item === adminLink}
                    renderItem={(item, state) =>
                        item === adminLink ? <AdminLinkLabel placement={state.placement} /> : tabLabel(item, state)
                    }
                    before={<Slot name='server.navigation.before' data={model.server} />}
                    after={<Slot name='server.navigation.after' data={model.server} />}
                />
            }
        >
            <Main>{children}</Main>
        </SubNavigationLayout>
    );
}
