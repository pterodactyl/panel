import { ServerContentBlock, useTranslation } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function UsersPage({ model, className }: ViewProps<'page.server.users'>) {
    const { t } = useTranslation();

    return (
        <PageStatus model={model} className={className}>
            <ServerContentBlock title={t('Users')} className={className}>
                <View name='server.subusers' />
            </ServerContentBlock>
        </PageStatus>
    );
}
