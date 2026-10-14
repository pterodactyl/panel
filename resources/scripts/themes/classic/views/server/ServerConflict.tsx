import { ScreenBlock, screenImages } from '@/sdk';
import type { ServerConflict as Conflict, ViewProps } from '@/sdk/theme';

const notices = {
    installing: {
        title: 'Running Installer',
        image: screenImages.installing,
        message: 'Your server should be ready soon, please try again in a few minutes.',
    },
    suspended: {
        title: 'Server Suspended',
        image: screenImages.error,
        message: 'This server is suspended and cannot be accessed.',
    },
    maintenance: {
        title: 'Node under Maintenance',
        image: screenImages.error,
        message: 'The node of this server is currently under maintenance.',
    },
    transferring: {
        title: 'Transferring',
        image: screenImages.restoring,
        message: 'Your server is being transferred to a new node, please check back later.',
    },
    'restoring-backup': {
        title: 'Restoring from Backup',
        image: screenImages.restoring,
        message: 'Your server is currently being restored from a backup, please check back in a few minutes.',
    },
} satisfies Record<Conflict, { title: string; image: string; message: string }>;

export default function ServerConflict({ model, className }: ViewProps<'server.conflict'>) {
    if (!model.kind) {
        return null;
    }

    const { title, image, message } = notices[model.kind];

    return <ScreenBlock title={title} image={image} message={message} className={className} />;
}
