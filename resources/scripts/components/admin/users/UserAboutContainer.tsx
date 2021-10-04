import updateUser, { Values } from '@/api/admin/users/updateUser';
import UserDeleteButton from '@/components/admin/users/UserDeleteButton';
import UserForm from '@/components/admin/users/UserForm';
import { Context } from '@/components/admin/users/UserRouter';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import { FormikHelpers } from 'formik';
import React from 'react';
import { useHistory } from 'react-router-dom';
import tw from 'twin.macro';

const UserAboutContainer = () => {
    const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const user = Context.useStoreState(state => state.user);
    const setUser = Context.useStoreActions(actions => actions.setUser);

    if (user === undefined) {
        return (
            <></>
        );
    }

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('user');

        updateUser(user.id, values)
            .then(() => setUser({ ...user, ...values }))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'user', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <UserForm
            title={'Edit User'}
            initialValues={{
                username: user.username,
                email: user.email,
                adminRoleId: user.adminRoleId,
                password: '',
                rootAdmin: user.rootAdmin,
            }}
            onSubmit={submit}
            role={user?.relationships.role || null}
        >
            <div css={tw`flex`}>
                <UserDeleteButton
                    userId={user.id}
                    onDeleted={() => history.push('/admin/users')}
                />
            </div>
        </UserForm>
    );
};

export default UserAboutContainer;
