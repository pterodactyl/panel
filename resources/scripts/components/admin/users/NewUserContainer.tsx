import React from 'react';
import tw from 'twin.macro';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import FlashMessageRender from '@/components/FlashMessageRender';
import UserForm from '@/components/admin/users/UserForm';
import { useHistory } from 'react-router-dom';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { FormikHelpers } from 'formik';
import createUser, { Values } from '@/api/admin/users/createUser';

export default () => {
    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('user:create');

        createUser(values)
            .then(user => history.push(`/admin/users/${user.id}`))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'user:create', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <AdminContentBlock title={'New User'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Create User</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>Add a new user to the panel.</p>
                </div>
            </div>

            <FlashMessageRender byKey={'user:create'} css={tw`mb-4`}/>

            <UserForm title={'Create User'} onSubmit={submit} role={null}/>
        </AdminContentBlock>
    );
};
