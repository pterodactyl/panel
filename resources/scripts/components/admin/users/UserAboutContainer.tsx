import type { Actions } from 'easy-peasy';
import { useStoreActions } from 'easy-peasy';
import type { FormikHelpers } from 'formik';
import { useNavigate } from 'react-router-dom';

import type { UpdateUserValues } from '@/api/admin/users';
import { updateUser } from '@/api/admin/users';
import UserDeleteButton from '@/components/admin/users/UserDeleteButton';
import UserForm from '@/components/admin/users/UserForm';
import { Context } from '@/components/admin/users/UserRouter';
import type { ApplicationStore } from '@/state';
import tw from 'twin.macro';

const UserAboutContainer = () => {
    const navigate = useNavigate();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );

    const user = Context.useStoreState(state => state.user);
    const setUser = Context.useStoreActions(actions => actions.setUser);

    if (user === undefined) {
        return <></>;
    }

    const submit = (values: UpdateUserValues, { setSubmitting }: FormikHelpers<UpdateUserValues>) => {
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
                externalId: user.externalId,
                username: user.username,
                email: user.email,
                adminRoleId: user.adminRoleId,
                password: '',
                rootAdmin: user.isRootAdmin,
            }}
            onSubmit={submit}
            uuid={user.uuid}
            role={user.relationships.role || null}
        >
            <div css={tw`flex`}>
                <UserDeleteButton userId={user.id} onDeleted={() => navigate('/admin/users')} />
            </div>
        </UserForm>
    );
};

export default UserAboutContainer;
