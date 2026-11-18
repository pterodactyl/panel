import React, { useEffect, useState } from 'react';
import { Field, Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import createApiKey from '@/api/account/createApiKey';
import getServers from '@/api/getServers';
import { Server } from '@/api/server/getServer';
import { Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { ApiKey } from '@/api/account/getApiKeys';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Input, { Textarea } from '@/components/elements/Input';
import Label from '@/components/elements/Label';
import styled from 'styled-components/macro';
import ApiKeyModal from '@/components/dashboard/ApiKeyModal';

interface Values {
    description: string;
    allowedIps: string;
}

const CustomTextarea = styled(Textarea)`
    ${tw`h-32`}
`;

export default ({ onKeyCreated }: { onKeyCreated: (key: ApiKey) => void }) => {
    const [apiKey, setApiKey] = useState('');
    const [restricted, setRestricted] = useState(false);
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);
    const [selectedServers, setSelectedServers] = useState<string[]>([]);
    const [servers, setServers] = useState<Server[]>([]);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const permissions = useStoreState((state: ApplicationStore) => state.permissions.data);
    const getPermissions = useStoreActions((actions: Actions<ApplicationStore>) => actions.permissions.getPermissions);

    useEffect(() => {
        if (!restricted) {
            return;
        }

        if (Object.keys(permissions).length === 0) {
            getPermissions().catch((error) => {
                console.error(error);
                addError({ key: 'account', message: httpErrorToHuman(error) });
            });
        }

        if (servers.length === 0) {
            getServers({})
                .then((data) => setServers(data.items))
                .catch((error) => {
                    console.error(error);
                    addError({ key: 'account', message: httpErrorToHuman(error) });
                });
        }
    }, [restricted]);

    const togglePermission = (permission: string) => {
        setSelectedPermissions((state) =>
            state.includes(permission) ? state.filter((p) => p !== permission) : [...state, permission]
        );
    };

    const toggleServer = (uuid: string) => {
        setSelectedServers((state) =>
            state.includes(uuid) ? state.filter((s) => s !== uuid) : [...state, uuid]
        );
    };

    const submit = (values: Values, { setSubmitting, resetForm }: FormikHelpers<Values>) => {
        clearFlashes('account');
        createApiKey(
            values.description,
            values.allowedIps,
            restricted ? selectedPermissions : null,
            restricted ? selectedServers : null
        )
            .then(({ secretToken, ...key }) => {
                resetForm();
                setSubmitting(false);
                setRestricted(false);
                setSelectedPermissions([]);
                setSelectedServers([]);
                setApiKey(`${key.identifier}${secretToken}`);
                onKeyCreated(key);
            })
            .catch((error) => {
                console.error(error);

                addError({ key: 'account', message: httpErrorToHuman(error) });
                setSubmitting(false);
            });
    };

    return (
        <>
            <ApiKeyModal visible={apiKey.length > 0} onModalDismissed={() => setApiKey('')} apiKey={apiKey} />
            <Formik
                onSubmit={submit}
                initialValues={{ description: '', allowedIps: '' }}
                validationSchema={object().shape({
                    allowedIps: string(),
                    description: string().required().min(4),
                })}
            >
                {({ isSubmitting }) => (
                    <Form>
                        <SpinnerOverlay visible={isSubmitting} />
                        <FormikFieldWrapper
                            label={'Description'}
                            name={'description'}
                            description={'A description of this API key.'}
                            css={tw`mb-6`}
                        >
                            <Field name={'description'} as={Input} />
                        </FormikFieldWrapper>
                        <FormikFieldWrapper
                            label={'Allowed IPs'}
                            name={'allowedIps'}
                            description={
                                'Leave blank to allow any IP address to use this API key, otherwise provide each IP address on a new line.'
                            }
                        >
                            <Field name={'allowedIps'} as={CustomTextarea} />
                        </FormikFieldWrapper>
                        <div css={tw`mt-6`}>
                            <label css={tw`flex items-center cursor-pointer select-none`}>
                                <Input
                                    type={'checkbox'}
                                    css={tw`w-auto mr-2`}
                                    checked={restricted}
                                    onChange={() => setRestricted((s) => !s)}
                                />
                                <Label css={tw`mb-0 cursor-pointer`}>Restrict this key</Label>
                            </label>
                            <p css={tw`text-xs text-neutral-400 mt-1`}>
                                Limit this key to specific permissions and servers. An unrestricted key can perform any
                                action your account can perform, on every server you have access to.
                            </p>
                        </div>
                        {restricted && (
                            <div css={tw`mt-6`}>
                                <Label>Permissions</Label>
                                <p css={tw`text-xs text-neutral-400 mb-2`}>
                                    Select the permissions this key is allowed to use. Leaving every box unchecked
                                    creates an unrestricted key.
                                </p>
                                {Object.keys(permissions).map((group) => (
                                    <div key={group} css={tw`mb-2`}>
                                        <p css={tw`text-sm text-neutral-300 uppercase`}>{group}</p>
                                        <div css={tw`flex flex-wrap`}>
                                            {Object.keys(permissions[group].keys).map((key) => (
                                                <label
                                                    key={`${group}.${key}`}
                                                    css={tw`flex items-center mr-4 mt-1 cursor-pointer select-none text-sm text-neutral-200`}
                                                >
                                                    <Input
                                                        type={'checkbox'}
                                                        css={tw`w-auto mr-1`}
                                                        checked={selectedPermissions.includes(`${group}.${key}`)}
                                                        onChange={() => togglePermission(`${group}.${key}`)}
                                                    />
                                                    {key}
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                                <div css={tw`mt-4`}>
                                    <Label>Servers</Label>
                                    <p css={tw`text-xs text-neutral-400 mb-2`}>
                                        Select the servers this key may interact with. Leaving every box unchecked
                                        allows access to all of your servers.
                                    </p>
                                    {servers.map((server) => (
                                        <label
                                            key={server.uuid}
                                            css={tw`flex items-center mt-1 cursor-pointer select-none text-sm text-neutral-200`}
                                        >
                                            <Input
                                                type={'checkbox'}
                                                css={tw`w-auto mr-2`}
                                                checked={selectedServers.includes(server.uuid)}
                                                onChange={() => toggleServer(server.uuid)}
                                            />
                                            {server.name}
                                        </label>
                                    ))}
                                </div>
                            </div>
                        )}
                        <div css={tw`flex justify-end mt-6`}>
                            <Button>Create</Button>
                        </div>
                    </Form>
                )}
            </Formik>
        </>
    );
};
