import { ScreenBlock, screenImages, useTranslation, type Translation } from '@/sdk';
import type { ServerConflict as Conflict, ViewProps } from '@/sdk/theme';

const notices = (t: Translation['t']) =>
    ({
        installing: {
            title: t('Running Installer'),
            image: screenImages.installing,
            message: t('Your server should be ready soon, please try again in a few minutes.'),
        },
        suspended: {
            title: t('Server Suspended'),
            image: screenImages.error,
            message: t('This server is suspended and cannot be accessed.'),
        },
        maintenance: {
            title: t('Node under Maintenance'),
            image: screenImages.error,
            message: t('The node of this server is currently under maintenance.'),
        },
        transferring: {
            title: t('Transferring'),
            image: screenImages.restoring,
            message: t('Your server is being transferred to a new node, please check back later.'),
        },
        'restoring-backup': {
            title: t('Restoring from Backup'),
            image: screenImages.restoring,
            message: t('Your server is currently being restored from a backup, please check back in a few minutes.'),
        },
    }) satisfies Record<Conflict, { title: string; image: string; message: string }>;

export default function ServerConflict({ model, className }: ViewProps<'server.conflict'>) {
    const { t } = useTranslation();

    if (!model.kind) {
        return null;
    }

    const { title, image, message } = notices(t)[model.kind];

    return <ScreenBlock title={title} image={image} message={message} className={className} />;
}
