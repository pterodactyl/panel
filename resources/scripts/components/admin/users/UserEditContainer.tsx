import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { useHistory, useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import { User } from '@/api/admin/users/getUsers';
import getUser from '@/api/admin/users/getUser';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';
import AdminBox from '@/components/admin/AdminBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import Field from '@/components/elements/Field';
import Button from '@/components/elements/Button';
import updateUser, { Values } from '@/api/admin/users/updateUser';
import UserDeleteButton from '@/components/admin/users/UserDeleteButton';

interface ctx {
    user: User | undefined;
    setUser: Action<ctx, User | undefined>;
}

export const Context = createContextStore<ctx>({
    user: undefined,

    setUser: action((state, payload) => {
        state.user = payload;
    }),
});

export interface Params {
    title: string;
    initialValues?: Values;
    children?: React.ReactNode;

    onSubmit: (values: Values, helpers: FormikHelpers<Values>) => void;
    exists?: boolean;
}

export function InformationContainer ({ title, initialValues, children, onSubmit, exists }: Params) {
    const submit = (values: Values, helpers: FormikHelpers<Values>) => {
        onSubmit(values, helpers);
    };

    if (!initialValues) {
        initialValues = {
            username: '',
            email: '',
            firstName: '',
            lastName: '',
            password: '',
            roleId: 0,
        };
    }

    return (
        <Formik
            onSubmit={submit}
            initialValues={initialValues}
            validationSchema={object().shape({
                username: string().min(1).max(32),
                email: string(),
                firstName: string(),
                lastName: string(),
                password: exists ? string() : string().required(),
            })}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <>
                        <AdminBox title={title} css={tw`relative`}>
                            <SpinnerOverlay visible={isSubmitting}/>

                            <Form css={tw`mb-0`}>
                                <div css={tw`md:w-full md:flex md:flex-row`}>
                                    <div css={tw`md:w-full md:flex md:flex-col md:mr-4 mt-6 md:mt-0`}>
                                        <Field
                                            id={'username'}
                                            name={'username'}
                                            label={'Username'}
                                            type={'text'}
                                        />
                                    </div>

                                    <div css={tw`md:w-full md:flex md:flex-col md:ml-4 mt-6 md:mt-0`}>
                                        <Field
                                            id={'email'}
                                            name={'email'}
                                            label={'Email Address'}
                                            type={'email'}
                                        />
                                    </div>
                                </div>

                                <div css={tw`md:w-full md:flex md:flex-row mt-6`}>
                                    <div css={tw`md:w-full md:flex md:flex-col md:mr-4 mt-6 md:mt-0`}>
                                        <Field
                                            id={'firstName'}
                                            name={'firstName'}
                                            label={'First Name'}
                                            type={'text'}
                                        />
                                    </div>

                                    <div css={tw`md:w-full md:flex md:flex-col md:ml-4 mt-6 md:mt-0`}>
                                        <Field
                                            id={'lastName'}
                                            name={'lastName'}
                                            label={'Last Name'}
                                            type={'text'}
                                        />
                                    </div>
                                </div>

                                <div css={tw`md:w-full md:flex md:flex-row mt-6`}>
                                    <div css={tw`md:w-full md:flex md:flex-col md:mr-4 mt-6 md:mt-0`}>
                                        <Field
                                            id={'password'}
                                            name={'password'}
                                            label={'Password'}
                                            type={'password'}
                                            placeholder={'••••••••'}
                                            autoComplete={'new-password'}
                                        />
                                    </div>

                                    <div css={tw`md:w-full md:flex md:flex-col md:ml-4 mt-6 md:mt-0`}/>
                                </div>

                                <div css={tw`w-full flex flex-row items-center mt-6`}>
                                    {children}
                                    <div css={tw`flex ml-auto`}>
                                        <Button type={'submit'} disabled={isSubmitting || !isValid}>
                                            Save
                                        </Button>
                                    </div>
                                </div>
                            </Form>
                        </AdminBox>
                    </>
                )
            }
        </Formik>
    );
}

function EditInformationContainer () {
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
        <InformationContainer
            title={'Edit User'}
            initialValues={{
                username: user.username,
                email: user.email,
                firstName: user.firstName,
                lastName: user.lastName,
                roleId: user.roleId,
                password: '',
            }}
            onSubmit={submit}
            exists
        >
            <div css={tw`flex`}>
                <UserDeleteButton
                    userId={user.id}
                    onDeleted={() => history.push('/admin/users')}
                />
            </div>
        </InformationContainer>
    );
}

function UserEditContainer () {
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const user = Context.useStoreState(state => state.user);
    const setUser = Context.useStoreActions(actions => actions.setUser);

    useEffect(() => {
        clearFlashes('user');

        getUser(Number(match.params?.id))
            .then(user => setUser(user))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'user', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || user === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'user'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'User - ' + user.id}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{user.firstName} {user.lastName}</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>{user.email}</p>
                </div>
            </div>

            <FlashMessageRender byKey={'user'} css={tw`mb-4`}/>

            <EditInformationContainer/>
        </AdminContentBlock>
    );
}

export default () => {
    return (
        <Context.Provider>
            <UserEditContainer/>
        </Context.Provider>
    );
};
