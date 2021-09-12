import FormikSwitch from '@/components/elements/FormikSwitch';
import React from 'react';
import tw from 'twin.macro';
import { action, Action, createContextStore } from 'easy-peasy';
import { User } from '@/api/admin/users/getUsers';
import AdminBox from '@/components/admin/AdminBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers } from 'formik';
import { bool, object, string } from 'yup';
import { Role } from '@/api/admin/roles/getRoles';
import { Values } from '@/api/admin/users/updateUser';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import RoleSelect from '@/components/admin/users/RoleSelect';

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

    role: Role | null;
}

export default function UserForm ({ title, initialValues, children, onSubmit, role }: Params) {
    const submit = (values: Values, helpers: FormikHelpers<Values>) => {
        onSubmit(values, helpers);
    };

    if (!initialValues) {
        initialValues = {
            username: '',
            email: '',
            password: '',
            adminRoleId: 0,
            rootAdmin: false,
        };
    }

    return (
        <Formik
            onSubmit={submit}
            initialValues={initialValues}
            validationSchema={object().shape({
                username: string().min(1).max(32),
                email: string(),
                rootAdmin: bool().required(),
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
                                            id={'password'}
                                            name={'password'}
                                            label={'Password'}
                                            type={'password'}
                                            placeholder={'••••••••'}
                                            autoComplete={'new-password'}
                                        />
                                    </div>

                                    <div css={tw`md:w-full md:flex md:flex-col md:ml-4 mt-6 md:mt-0`}>
                                        <RoleSelect selected={role}/>
                                    </div>
                                </div>

                                <div css={tw`w-full flex flex-row mb-6`}>
                                    <div css={tw`w-full bg-neutral-800 border border-neutral-900 shadow-inner mt-6 p-4 rounded`}>
                                        <FormikSwitch
                                            name={'rootAdmin'}
                                            label={'Root Admin'}
                                            description={'Should this user be a root administrator?'}
                                        />
                                    </div>
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
