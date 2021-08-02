import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import React, { useEffect, useState } from 'react';
// import { useHistory } from 'react-router';
import { useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import { object, string } from 'yup';
import { Role } from '@/api/admin/roles/getRoles';
import getRole from '@/api/admin/roles/getRole';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';
import { Form, Formik, FormikHelpers } from 'formik';
import updateRole from '@/api/admin/roles/updateRole';
import AdminBox from '@/components/admin/AdminBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Field from '@/components/elements/Field';
import Button from '@/components/elements/Button';

interface ctx {
    role: Role | undefined;
    setRole: Action<ctx, Role | undefined>;
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
    // const history = useHistory();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const role = Context.useStoreState(state => state.role);
    const setRole = Context.useStoreActions(actions => actions.setRole);

    if (role === undefined) {
        return (
            <></>
        );
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
            {
                ({ isSubmitting, isValid }) => (
                    <React.Fragment>
                        <AdminBox title={'Edit Role'} css={tw`relative`}>
                            <SpinnerOverlay visible={isSubmitting}/>

                            <Form css={tw`mb-0`}>
                                <div>
                                    <Field
                                        id={'name'}
                                        name={'name'}
                                        label={'Name'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`mt-6`}>
                                    <Field
                                        id={'description'}
                                        name={'description'}
                                        label={'description'}
                                        type={'text'}
                                    />
                                </div>

                                <div css={tw`w-full flex flex-row items-center mt-6`}>
                                    {/* <div css={tw`flex`}> */}
                                    {/*     <RoleDeleteButton */}
                                    {/*         roleId={role.id} */}
                                    {/*         onDeleted={() => history.push('/admin/roles')} */}
                                    {/*     /> */}
                                    {/* </div> */}

                                    <div css={tw`flex ml-auto`}>
                                        <Button type={'submit'} disabled={isSubmitting || !isValid}>
                                            Save
                                        </Button>
                                    </div>
                                </div>
                            </Form>
                        </AdminBox>
                    </React.Fragment>
                )
            }
        </Formik>
    );
};

const RoleEditContainer = () => {
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const role = Context.useStoreState(state => state.role);
    const setRole = Context.useStoreActions(actions => actions.setRole);

    useEffect(() => {
        clearFlashes('role');

        getRole(Number(match.params?.id))
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
                <FlashMessageRender byKey={'role'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Role - ' + role.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{role.name}</h2>
                    {
                        (role.description || '').length < 1 ?
                            <p css={tw`text-base text-neutral-400`}>
                                <span css={tw`italic`}>No description</span>
                            </p>
                            :
                            <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>{role.description}</p>
                    }
                </div>
            </div>

            <FlashMessageRender byKey={'role'} css={tw`mb-4`}/>

            <EditInformationContainer/>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <RoleEditContainer/>
        </Context.Provider>
    );
};
