import ScreenBlock from '@/components/elements/ScreenBlock';
import ServerInstallSvg from '@/assets/images/server_installing.svg';
import ServerErrorSvg from '@/assets/images/server_error.svg';
import ServerRestoreSvg from '@/assets/images/server_restore.svg';
import { useCurrentServer } from '@/api/server/queries';
import { isInstallingStatus } from '@/lib/serverStatus';

type ConflictState = {
    title: string;
    image: string;
    message: string;
};

type ConflictStatus = string | null;

function resolveConflictState(
    status: ConflictStatus,
    isTransferring: boolean,
    isNodeUnderMaintenance: boolean
): ConflictState {
    if (isInstallingStatus(status)) {
        return {
            title: 'Running Installer',
            image: ServerInstallSvg,
            message: 'Your server should be ready soon, please try again in a few minutes.',
        };
    }

    if (status === 'suspended') {
        return {
            title: 'Server Suspended',
            image: ServerErrorSvg,
            message: 'This server is suspended and cannot be accessed.',
        };
    }

    if (isNodeUnderMaintenance) {
        return {
            title: 'Node under Maintenance',
            image: ServerErrorSvg,
            message: 'The node of this server is currently under maintenance.',
        };
    }

    if (isTransferring) {
        return {
            title: 'Transferring',
            image: ServerRestoreSvg,
            message: 'Your server is being transferred to a new node, please check back later.',
        };
    }

    return {
        title: 'Restoring from Backup',
        image: ServerRestoreSvg,
        message: 'Your server is currently being restored from a backup, please check back in a few minutes.',
    };
}

export default function ConflictStateRenderer() {
    const server = useCurrentServer();
    const status = server?.attributes.status || null;
    const isTransferring = server?.attributes.is_transferring || false;
    const isNodeUnderMaintenance = server?.attributes.is_node_under_maintenance || false;

    const state = resolveConflictState(status, isTransferring, isNodeUnderMaintenance);

    return <ScreenBlock title={state.title} image={state.image} message={state.message} />;
}
