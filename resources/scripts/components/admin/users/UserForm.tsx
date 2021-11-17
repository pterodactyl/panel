import CopyOnClick from '@/components/elements/CopyOnClick';
import FormikSwitch from '@/components/elements/FormikSwitch';
import Input from '@/components/elements/Input';
import Label from '@/components/elements/Label';
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
import Field, { FieldRow } from '@/components/elements/Field';
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

    uuid?: string;
    role: Role | null;
}

export default function UserForm ({ title, initialValues, children, onSubmit, uuid, role }: Params) {
    const submit = (values: Values, helpers: FormikHelpers<Values>) => {
        onSubmit(values, helpers);
    };

    if (!initialValues) {
        initialValues = {
            externalId: '',
            username: '',
            email: '',
            password: '',
            adminRoleId: null,
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
                                <FieldRow>
                                    {uuid &&
                                        <div>
                                            <Label>UUID</Label>
                                            <CopyOnClick text={uuid}>
                                                <Input
                                                    type={'text'}
                                                    value={uuid}
                                                    readOnly
                                                />
                                            </CopyOnClick>
                                        </div>
                                    }
                                    <Field
                                        id={'externalId'}
                                        name={'externalId'}
                                        label={'External ID'}
                                        type={'text'}
                                        description={'Used by external integrations, this field should not be modified unless you know what you are doing.'}
                                    />
                                    <Field
                                        id={'username'}
                                        name={'username'}
                                        label={'Username'}
                                        type={'text'}
                                        description={'The user\'s username, what else would go here?'}
                                    />
                                    <Field
                                        id={'email'}
                                        name={'email'}
                                        label={'Email Address'}
                                        type={'email'}
                                        description={'The user\'s email address, what else would go here?'}
                                    />
                                    <Field
                                        id={'password'}
                                        name={'password'}
                                        label={'Password'}
                                        type={'password'}
                                        placeholder={'••••••••'}
                                        autoComplete={'new-password'}
                                        /* TODO: Change description depending on if user is being created or updated. */
                                        description={'Leave empty to email the user a link where they will be required to set a password.'}
                                    />
                                    <RoleSelect selected={role}/>
                                </FieldRow>

                                {/* TODO: Remove toggle once role permissions are implemented. */}
                                <div css={tw`w-full flex flex-row mb-6`}>
                                    <div css={tw`w-full bg-neutral-800 border border-neutral-900 shadow-inner p-4 rounded`}>
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
                                            Save Changes
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
