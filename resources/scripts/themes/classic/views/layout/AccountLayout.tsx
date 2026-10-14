import { SubNavigation, SubNavigationLayout } from '@/sdk';
import { Main, Slot, type ViewProps } from '@/sdk/theme';
import { tabLabel } from './tabLabel';

export default function AccountLayout({ model, className, children }: ViewProps<'layout.account'>) {
    return (
        <SubNavigationLayout
            className={className}
            navigation={
                <SubNavigation
                    items={model.nav.items}
                    renderItem={tabLabel}
                    before={<Slot name='account.navigation.before' />}
                    after={<Slot name='account.navigation.after' />}
                />
            }
        >
            <Main>{children}</Main>
        </SubNavigationLayout>
    );
}
