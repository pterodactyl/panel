import type { AdminEgg } from '@/api/admin/eggs/queries';
import { useExportAdminEgg } from '@/api/admin/eggs/queries';
import Button from '@/components/elements/Button';
import { notifyHttpError } from '@/plugins/notifications';
import { useTranslation } from '@/sdk/localization';

interface Props {
    egg: AdminEgg;
}

const kebab = (value: string): string =>
    value
        .replaceAll(/\W+/g, '-')
        .replaceAll(/([a-z\d])([A-Z])/g, '$1-$2')
        .toLowerCase()
        .replaceAll(/^-+|-+$/g, '');

export default function ExportEggButton({ egg }: Props) {
    const { t } = useTranslation();
    const exportEgg = useExportAdminEgg(egg.attributes.id);

    const download = async () => {
        try {
            const { data: json } = await exportEgg.refetch({ throwOnError: true });

            if (!json) {
                return;
            }

            const blob = new Blob([json], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download = `egg-${kebab(egg.attributes.name)}.json`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        } catch (error) {
            notifyHttpError(error, t('Unable to export egg'));
        }
    };

    return (
        <Button isSecondary disabled={exportEgg.isFetching} isLoading={exportEgg.isFetching} onClick={download}>
            {t('Export')}
        </Button>
    );
}
