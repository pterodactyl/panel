import { useAdminNodesGroupedByLocation } from '@/api/admin/nodes/queries';
import Select from '@/components/ui/Select';
import Label from '@/components/elements/Label';
import { useTranslation } from '@/sdk/localization';

interface Props {
    id: string;
    value: string;
    onChange: (value: string) => void;
}

export default function DatabaseHostNodeSelect({ id, value, onChange }: Props) {
    const { t } = useTranslation();
    const { data: locations } = useAdminNodesGroupedByLocation();

    const groups = [
        {
            label: t('Global'),
            options: [{ value: '', label: t('None') }],
        },
        ...(locations ?? []).map((group) => ({
            label: `${group.location.attributes.long || group.location.attributes.short} (${group.location.attributes.short})`,
            options: group.nodes.map((node) => ({
                value: String(node.id),
                label: `${node.name} (${node.fqdn})`,
            })),
        })),
    ];

    return (
        <div>
            <Label htmlFor={id}>{t('Linked Node')}</Label>
            <Select
                id={id}
                value={value}
                placeholder={t('None')}
                groups={groups}
                disabled={locations === undefined}
                onChange={(value) => onChange(String(value))}
            />
            <p className='input-help'>
                {t('Default to this database host when adding databases to servers on selected node.')}
            </p>
        </div>
    );
}
