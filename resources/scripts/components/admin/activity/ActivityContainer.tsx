import AdminContentBlock from '@/components/admin/AdminContentBlock';
import { View } from '@/views/View';

export default function ActivityContainer() {
    return (
        <AdminContentBlock
            title='Admin · Activity'
            heading='Activity'
            description='Actions taken through the admin panel, newest first. Server and account activity stays on those screens.'
        >
            <View name='activity.log' scope='admin' />
        </AdminContentBlock>
    );
}
