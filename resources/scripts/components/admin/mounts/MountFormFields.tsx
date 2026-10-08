import type { AppForm } from '@/components/form';
import type { MountValues } from '@/api/admin/mounts/queries';
import TitledGreyBox from '@/components/elements/TitledGreyBox';

const validateMountName = (value: string): string | undefined => {
    if (value.length < 2) {
        return 'A name must be at least 2 characters.';
    }

    if (value.length > 64) {
        return 'A name must not exceed 64 characters.';
    }

    return undefined;
};

interface Props {
    form: AppForm<MountValues>;
}

const DetailsFields = ({ form }: Props) => (
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
                    label='Name'
                    description='A unique name used to identify this mount.'
                />
            )}
        </form.AppField>
        <form.AppField
            name='description'
            validators={{
                onChange: ({ value }) =>
                    value.length <= 191 ? undefined : 'A description must not exceed 191 characters.',
            }}
        >
            {(field) => (
                <field.TextField
                    type='text'
                    id='description'
                    label='Description'
                    description='A longer description for this mount.'
                />
            )}
        </form.AppField>
    </div>
);

const PathFields = ({ form }: Props) => (
    <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
        <form.AppField
            name='source'
            validators={{
                onChange: ({ value }) => (value.length >= 1 ? undefined : 'A source path must be provided.'),
            }}
        >
            {(field) => (
                <field.TextField
                    type='text'
                    id='source'
                    label='Source'
                    description='The path on the host system to mount into the container.'
                />
            )}
        </form.AppField>
        <form.AppField
            name='target'
            validators={{
                onChange: ({ value }) => (value.length >= 1 ? undefined : 'A target path must be provided.'),
            }}
        >
            {(field) => (
                <field.TextField
                    type='text'
                    id='target'
                    label='Target'
                    description='The path inside the container where the source will be mounted.'
                />
            )}
        </form.AppField>
    </div>
);

const AccessFields = ({ form }: Props) => (
    <div className='space-y-6'>
        <form.AppField name='readOnly'>
            {(field) => (
                <field.SwitchField
                    label='Read Only'
                    description='Mount this volume as read only inside the container.'
                />
            )}
        </form.AppField>
        <form.AppField name='userMountable'>
            {(field) => (
                <field.SwitchField
                    label='User Mountable'
                    description='Allow this mount to be added to servers by users.'
                />
            )}
        </form.AppField>
    </div>
);

export default function MountFormFields({ form }: Props) {
    return (
        <div className='space-y-6'>
            <TitledGreyBox title='Mount Details'>
                <DetailsFields form={form} />
            </TitledGreyBox>
            <TitledGreyBox title='Paths'>
                <PathFields form={form} />
            </TitledGreyBox>
            <TitledGreyBox title='Access'>
                <AccessFields form={form} />
            </TitledGreyBox>
        </div>
    );
}
