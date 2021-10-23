import { Egg, EggVariable, getEgg } from '@/api/admin/egg';
import updateServerStartup, { Values } from '@/api/admin/servers/updateServerStartup';
import EggSelect from '@/components/admin/servers/EggSelect';
import NestSelector from '@/components/admin/servers/NestSelector';
import FormikSwitch from '@/components/elements/FormikSwitch';
import React, { useEffect, useState } from 'react';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import Label from '@/components/elements/Label';
import { object } from 'yup';
import { Server, useServerFromRoute } from '@/api/admin/server';
import { InferModel } from '@/api/admin';

function ServerStartupLineContainer ({ egg, server }: { egg: Egg | null; server: Server }) {
    const { isSubmitting, setFieldValue } = useFormikContext();

    useEffect(() => {
        if (egg === null) {
            return;
        }

        if (server.eggId === egg.id) {
            setFieldValue('startup', server.container.startup);
            return;
        }

        // Whenever the egg is changed, set the server's startup command to the egg's default.
        setFieldValue('startup', egg.startup);
    }, [ egg ]);

    return (
        <AdminBox title={'Startup Command'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <div css={tw`mb-6`}>
                <Field
                    id={'startup'}
                    name={'startup'}
                    label={'Startup Command'}
                    type={'text'}
                    description={'Edit your server\'s startup command here. The following variables are available by default: {{SERVER_MEMORY}}, {{SERVER_IP}}, and {{SERVER_PORT}}.'}
                />
            </div>

            <div>
                <Label>Default Startup Command</Label>
                <Input value={egg?.startup || ''} readOnly/>
            </div>
        </AdminBox>
    );
}

function ServerServiceContainer ({ egg, setEgg, server }: { egg: Egg | null, setEgg: (value: Egg | null) => void, server: Server }) {
    const { isSubmitting } = useFormikContext();

    const [ nestId, setNestId ] = useState(server.nestId);

    return (
        <AdminBox title={'Service Configuration'} isLoading={isSubmitting} css={tw`w-full`}>
            <div css={tw`mb-6`}>
                <NestSelector selectedNestId={nestId} onNestSelect={setNestId}/>
            </div>
            <div css={tw`mb-6`}>
                <EggSelect nestId={nestId} selectedEggId={egg?.id} onEggSelect={setEgg}/>
            </div>
            <div css={tw`bg-neutral-800 border border-neutral-900 shadow-inner p-4 rounded`}>
                <FormikSwitch name={'skipScript'} label={'Skip Egg Install Script'} description={'Soon™'}/>
            </div>
        </AdminBox>
    );
}

function ServerImageContainer () {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox title={'Image Configuration'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

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
        </AdminBox>
    );
}

function ServerVariableContainer ({ variable, defaultValue }: { variable: EggVariable, defaultValue: string }) {
    const key = 'environment.' + variable.environmentVariable;

    const { isSubmitting, setFieldValue } = useFormikContext();

    useEffect(() => {
        setFieldValue(key, defaultValue);
    }, [ variable, defaultValue ]);

    return (
        <AdminBox css={tw`relative w-full`} title={<p css={tw`text-sm uppercase`}>{variable.name}</p>}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Field
                id={key}
                name={key}
                type={'text'}
                placeholder={variable.defaultValue}
                description={variable.description}
            />
        </AdminBox>
    );
}

function ServerStartupForm ({ egg, setEgg, server }: { egg: Egg | null, setEgg: (value: Egg | null) => void; server: Server }) {
    const { isSubmitting, isValid } = useFormikContext();

    return (
        <Form>
            <div css={tw`flex flex-col mb-16`}>
                <div css={tw`flex flex-row mb-6`}>
                    <ServerStartupLineContainer
                        egg={egg}
                        server={server}
                    />
                </div>

                <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-6`}>
                    <div css={tw`flex`}>
                        <ServerServiceContainer
                            egg={egg}
                            setEgg={setEgg}
                            server={server}
                        />
                    </div>

                    <div css={tw`flex`}>
                        <ServerImageContainer/>
                    </div>
                </div>

                <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8`}>
                    {egg?.relationships.variables?.map((v, i) => (
                        <ServerVariableContainer
                            key={i}
                            variable={v}
                            defaultValue={server.relationships?.variables?.find(v2 => v.eggId === v2.eggId && v.environmentVariable === v2.environmentVariable)?.serverValue || v.defaultValue}
                        />
                    ))}
                </div>

                <div css={tw`bg-neutral-700 rounded shadow-md py-2 pr-6 mt-6`}>
                    <div css={tw`flex flex-row`}>
                        <Button type="submit" size="small" css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                            Save Changes
                        </Button>
                    </div>
                </div>
            </div>
        </Form>
    );
}

export default () => {
    const { data: server } = useServerFromRoute();
    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ egg, setEgg ] = useState<InferModel<typeof getEgg> | null>(null);

    useEffect(() => {
        if (!server) return;

        getEgg(server.eggId)
            .then(egg => setEgg(egg))
            .catch(error => console.error(error));
    }, [ server?.eggId ]);

    if (!server) return null;

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('server');

        updateServerStartup(server.id, values)
            // .then(s => {
            //     mutate(data => { ...data, ...s });
            // })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'server', error });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                startup: server.container.startup,
                // Don't ask.
                environment: Object.fromEntries(egg?.relationships.variables.map(v => [ v.environmentVariable, '' ]) || []),
                image: server.container.image,
                eggId: server.eggId,
                skipScripts: false,
            }}
            validationSchema={object().shape({
            })}
        >
            <ServerStartupForm
                egg={egg}
                // @ts-ignore
                setEgg={setEgg}
                server={server}
            />
        </Formik>
    );
};
