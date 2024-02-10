import ServersTable from '@/components/admin/servers/ServersTable';
import { Context } from '@/components/admin/users/UserRouter';

function UserServers() {
    const user = Context.useStoreState(state => state.user);

    return <ServersTable filters={{ owner_id: user?.id?.toString?.() }} />;
}

export default UserServers;
