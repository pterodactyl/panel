import type { AppForm } from '@/components/form';
import type { MountValues } from '@/api/admin/mounts/queries';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { validateMountName } from '@/components/admin/mounts/mountForm';
import { useTranslation } from '@/sdk/localization';

interface Props {
    form: AppForm<MountValues>;
}

const DetailsFields = ({ form }: Props) => {
    const { t } = useTranslation();

    return (
        <div className='space-y-6'>
            <form.AppField
                name='name'
                validators={{
                    onChange: ({ value }) => validateMountName(value),
                }}
            >
                {(field) => (
                    <field.TextField
                        type='text'
                        id='name'
                        label={t('Name')}
                        description={t('A unique name used to identify this mount.')}
                    />
                )}
            </form.AppField>
            <form.AppField
                name='description'
                validators={{
                    onChange: ({ value }) =>
                        value.length <= 191 ? undefined : t('A description must not exceed 191 characters.'),
                }}
            >
                {(field) => (
                    <field.TextField
                        type='text'
                        id='description'
                        label={t('Description')}
                        description={t('A longer description for this mount.')}
                    />
                )}
            </form.AppField>
        </div>
    );
};

const PathFields = ({ form }: Props) => {
    const { t } = useTranslation();

    return (
        <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
            <form.AppField
                name='source'
                validators={{
                    onChange: ({ value }) => (value.length >= 1 ? undefined : t('A source path must be provided.')),
                }}
            >
                {(field) => (
                    <field.TextField
                        type='text'
                        id='source'
                        label={t('Source')}
                        description={t('The path on the host system to mount into the container.')}
                    />
                )}
            </form.AppField>
            <form.AppField
                name='target'
                validators={{
                    onChange: ({ value }) => (value.length >= 1 ? undefined : t('A target path must be provided.')),
                }}
            >
                {(field) => (
                    <field.TextField
                        type='text'
                        id='target'
                        label={t('Target')}
                        description={t('The path inside the container where the source will be mounted.')}
                    />
                )}
            </form.AppField>
        </div>
    );
};

const AccessFields = ({ form }: Props) => {
    const { t } = useTranslation();

    return (
        <div className='space-y-6'>
            <form.AppField name='readOnly'>
                {(field) => (
                    <field.SwitchField
                        label={t('Read Only')}
                        description={t('Mount this volume as read only inside the container.')}
                    />
                )}
            </form.AppField>
            <form.AppField name='userMountable'>
                {(field) => (
                    <field.SwitchField
                        label={t('User Mountable')}
                        description={t('Allow this mount to be added to servers by users.')}
                    />
                )}
            </form.AppField>
        </div>
    );
};

export default function MountFormFields({ form }: Props) {
    const { t } = useTranslation();

    return (
        <div className='space-y-6'>
            <TitledGreyBox title={t('Mount Details')}>
                <DetailsFields form={form} />
            </TitledGreyBox>
            <TitledGreyBox title={t('Paths')}>
                <PathFields form={form} />
            </TitledGreyBox>
            <TitledGreyBox title={t('Access')}>
                <AccessFields form={form} />
            </TitledGreyBox>
        </div>
    );
}
