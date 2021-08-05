import React from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import MountForm from '@/components/admin/mounts/MountForm';
import { FormikHelpers } from 'formik';
import { useHistory } from 'react-router-dom';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import createMount from '@/api/admin/mounts/createMount';

export default () => {
    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = ({ name, description, source, target, readOnly, userMountable }: any, { setSubmitting }: FormikHelpers<any>) => {
        clearFlashes('mount:create');

        createMount(name, description, source, target, readOnly === '1', userMountable === '1')
            .then(mount => history.push(`/admin/mounts/${mount.id}`))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'mount:create', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <AdminContentBlock title={'New Mount'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Create Mount</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>Add a new mount to the panel.</p>
                </div>
            </div>

            <FlashMessageRender byKey={'mount:create'} css={tw`mb-4`}/>

            <MountForm
                action={'Create'}
                title={'Create Mount'}
                onSubmit={submit}
            />
        </AdminContentBlock>
    );
};
