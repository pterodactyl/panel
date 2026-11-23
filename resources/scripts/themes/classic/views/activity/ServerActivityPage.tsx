import { ServerContentBlock, useTranslation } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function ServerActivityPage({ className }: ViewProps<'page.server.activity'>) {
    const { t } = useTranslation();

    return (
        <ServerContentBlock title={t('Activity Log')} className={className}>
            <View name='activity.log' scope='server' />
        </ServerContentBlock>
    );
}
