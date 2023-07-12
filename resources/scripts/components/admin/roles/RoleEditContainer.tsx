import type { Action, Actions } from 'easy-peasy';
import { action, createContextStore, useStoreActions } from 'easy-peasy';
import type { FormikHelpers } from 'formik';
import { Form, Formik } from 'formik';
import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import tw from 'twin.macro';
import { object, string } from 'yup';

import { getRole, updateRole } from '@/api/admin/roles';
import FlashMessageRender from '@/components/FlashMessageRender';
import AdminBox from '@/components/admin/AdminBox';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import RoleDeleteButton from '@/components/admin/roles/RoleDeleteButton';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import Spinner from '@/components/elements/Spinner';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import type { UserRole } from '@definitions/admin';
import type { ApplicationStore } from '@/state';

interface ctx {
    role: UserRole | undefined;
    setRole: Action<ctx, UserRole | undefined>;
}

export const Context = createContextStore<ctx>({
    role: undefined,

    setRole: action((state, payload) => {
        state.role = payload;
    }),
});

interface Values {
    name: string;
    description: string;
}

const EditInformationContainer = () => {
    const navigate = useNavigate();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );

    const role = Context.useStoreState(state => state.role);
    const setRole = Context.useStoreActions(actions => actions.setRole);

    if (role === undefined) {
        return <></>;
    }

    const submit = ({ name, description }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('role');

        updateRole(role.id, name, description)
            .then(() => setRole({ ...role, name, description }))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'role', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                name: role.name,
                description: role.description || '',
            }}
            validationSchema={object().shape({
                name: string().required().min(1),
                description: string().max(255, ''),
            })}
        >
            {({ isSubmitting, isValid }) => (
                <>
                    <AdminBox title={'Edit Role'} css={tw`relative`}>
                        <SpinnerOverlay visible={isSubmitting} />

                        <Form css={tw`mb-0`}>
                            <div>
                                <Field id={'name'} name={'name'} label={'Name'} type={'text'} />
                            </div>

                            <div css={tw`mt-6`}>
                                <Field id={'description'} name={'description'} label={'description'} type={'text'} />
                            </div>

                            <div css={tw`w-full flex flex-row items-center mt-6`}>
                                <div css={tw`flex`}>
                                    <RoleDeleteButton roleId={role.id} onDeleted={() => navigate('/admin/roles')} />
                                </div>

                                <div css={tw`flex ml-auto`}>
                                    <Button type={'submit'} disabled={isSubmitting || !isValid}>
                                        Save Changes
                                    </Button>
                                </div>
                            </div>
                        </Form>
                    </AdminBox>
                </>
            )}
        </Formik>
    );
};

const RoleEditContainer = () => {
    const params = useParams<'id'>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );
    const [loading, setLoading] = useState(true);

    const role = Context.useStoreState(state => state.role);
    const setRole = Context.useStoreActions(actions => actions.setRole);

    useEffect(() => {
        clearFlashes('role');

        getRole(Number(params.id))
            .then(role => setRole(role))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'role', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || role === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'role'} css={tw`mb-4`} />

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'} />
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Role - ' + role.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{role.name}</h2>
                    {(role.description || '').length < 1 ? (
                        <p css={tw`text-base text-neutral-400`}>
                            <span css={tw`italic`}>No description</span>
                        </p>
                    ) : (
                        <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                            {role.description}
                        </p>
                    )}
                </div>
            </div>

            <FlashMessageRender byKey={'role'} css={tw`mb-4`} />

            <EditInformationContainer />
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <RoleEditContainer />
        </Context.Provider>
    );
};
