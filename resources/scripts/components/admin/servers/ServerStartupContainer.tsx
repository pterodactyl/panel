import EggSelect from '@/components/admin/servers/EggSelect';
import NestSelect from '@/components/admin/servers/NestSelect';
import FormikSwitch from '@/components/elements/FormikSwitch';
import React, { useState } from 'react';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';
import { object } from 'yup';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, useFormikContext } from 'formik';
import { Context } from '@/components/admin/servers/ServerRouter';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import Label from '@/components/elements/Label';

// interface Values {
//     startupCommand: string;
//     image: string;
//
//     eggId: number;
//     skipScripts: boolean;
// }

function ServerStartupLineContainer () {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox title={'Startup Command'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Form css={tw`mb-0`}>
                <div css={tw`mb-6`}>
                    <Field
                        id={'startupCommand'}
                        name={'startupCommand'}
                        label={'Startup Command'}
                        type={'text'}
                        description={'Edit your server\'s startup command here. The following variables are available by default: {{SERVER_MEMORY}}, {{SERVER_IP}}, and {{SERVER_PORT}}.'}
                    />
                </div>

                <div>
                    <Label>Default Startup Command</Label>
                    <Input disabled/>
                </div>
            </Form>
        </AdminBox>
    );
}

function ServerServiceContainer ({ nestId: nestId2, eggId }: { nestId: number | null; eggId: number | null }) {
    const { isSubmitting } = useFormikContext();

    const [ nestId, setNestId ] = useState<number | null>(nestId2);

    return (
        <AdminBox title={'Service Configuration'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Form css={tw`mb-0`}>
                <div css={tw`mb-6`}>
                    <NestSelect nestId={nestId} setNestId={setNestId}/>
                </div>

                <div css={tw`mb-6`}>
                    <EggSelect nestId={nestId} eggId={eggId || undefined}/>
                </div>

                <div css={tw`bg-neutral-800 border border-neutral-900 shadow-inner p-4 rounded`}>
                    <FormikSwitch
                        name={'skipScript'}
                        label={'Skip Egg Install Script'}
                        description={'SoonTM'}
                    />
                </div>
            </Form>
        </AdminBox>
    );
}

function ServerImageContainer () {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox title={'Image Configuration'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Form css={tw`mb-0`}>
                <div css={tw`md:w-full md:flex md:flex-col`}>
                    <div>
                        <Field
                            id={'image'}
                            name={'image'}
                            label={'Docker Image'}
                            type={'text'}
                        />
                    </div>
                </div>
            </Form>
        </AdminBox>
    );
}

export default function ServerStartupContainer () {
    const { clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const server = Context.useStoreState(state => state.server);

    if (server === undefined) {
        return (
            <></>
        );
    }

    const submit = () => {
        clearFlashes('server');
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                startupCommand: server.container.startupCommand,
                image: server.container.image,
                eggId: 0,
                skipScripts: false,
            }}
            validationSchema={object().shape({})}
        >
            {({ isSubmitting, isValid }) => (
                <div css={tw`flex flex-col`}>
                    <div css={tw`flex flex-row mb-8`}>
                        <ServerStartupLineContainer/>
                    </div>

                    <div css={tw`grid grid-cols-2 gap-x-8 mb-8`}>
                        <div css={tw`flex`}>
                            <ServerServiceContainer nestId={server?.nestId || null} eggId={server?.eggId || null}/>
                        </div>
                        <div css={tw`flex`}>
                            <ServerImageContainer/>
                        </div>
                    </div>

                    {/*<div css={tw`grid gap-8 md:grid-cols-2`}>*/}
                    {/*    {variables.map((variable, i) => (*/}
                    {/*        <TitledGreyBox*/}
                    {/*            key={i}*/}
                    {/*            title={<p css={tw`text-sm uppercase`}>{variable.name}</p>}*/}
                    {/*        >*/}
                    {/*            <InputSpinner visible={false}>*/}
                    {/*                <Input*/}
                    {/*                    name={variable.envVariable}*/}
                    {/*                    defaultValue={variable.serverValue}*/}
                    {/*                    placeholder={variable.defaultValue}*/}
                    {/*                />*/}
                    {/*            </InputSpinner>*/}
                    {/*            <p css={tw`mt-1 text-xs text-neutral-300`}>*/}
                    {/*                {variable.description}*/}
                    {/*            </p>*/}
                    {/*        </TitledGreyBox>*/}
                    {/*    ))}*/}
                    {/*</div>*/}

                    <div css={tw`py-2 pr-6 mt-4 rounded shadow-md bg-neutral-700`}>
                        <div css={tw`flex flex-row`}>
                            <Button type="submit" size="small" css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                                Save Changes
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </Formik>
    );
}
