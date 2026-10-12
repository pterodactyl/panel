import { SubNavigation, SubNavigationLayout } from '@/sdk';
import { Main, Slot, type ViewProps } from '@/sdk/theme';

export default function AccountLayout({ model, children }: ViewProps<'layout.account'>) {
    return (
        <SubNavigationLayout
            navigation={
                <SubNavigation
                    items={model.nav.items}
                    before={<Slot name='account.navigation.before' />}
                    after={<Slot name='account.navigation.after' />}
                />
            }
        >
            <Main>{children}</Main>
        </SubNavigationLayout>
    );
}
