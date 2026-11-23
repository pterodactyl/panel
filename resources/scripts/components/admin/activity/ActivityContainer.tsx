import AdminContentBlock from '@/components/admin/AdminContentBlock';
import { View } from '@/views/View';
import { useTranslation } from '@/sdk/localization';

export default function ActivityContainer() {
    const { t } = useTranslation();

    return (
        <AdminContentBlock
            title={t('Admin · Activity')}
            heading={t('Activity')}
            description={t(
                'Actions taken through the admin panel, newest first. Server and account activity stays on those screens.'
            )}
        >
            <View name='activity.log' scope='admin' />
        </AdminContentBlock>
    );
}
