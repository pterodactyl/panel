import type { Actions } from 'easy-peasy';
import { useStoreActions } from 'easy-peasy';
import type { FormikHelpers } from 'formik';
import { Form, Formik, useField, useFormikContext } from 'formik';
import { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { object } from 'yup';

import type { InferModel } from '@/api/admin';
import type { Egg, EggVariable } from '@/api/admin/egg';
import { getEgg } from '@/api/admin/egg';
import type { Server } from '@/api/admin/server';
import { useServerFromRoute } from '@/api/admin/server';
import type { Values } from '@/api/admin/servers/updateServerStartup';
import updateServerStartup from '@/api/admin/servers/updateServerStartup';
import EggSelect from '@/components/admin/servers/EggSelect';
import NestSelector from '@/components/admin/servers/NestSelector';
import FormikSwitch from '@/components/elements/FormikSwitch';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import AdminBox from '@/components/admin/AdminBox';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Label from '@/components/elements/Label';
import type { ApplicationStore } from '@/state';

function ServerStartupLineContainer({ egg, server }: { egg: Egg | null; server: Server }) {
    const { isSubmitting, setFieldValue } = useFormikContext();

    useEffect(() => {
        if (egg === null) {
            return;
        }

        if (server.eggId === egg.id) {
            setFieldValue('image', server.container.image);
            setFieldValue('startup', server.container.startup || '');
            return;
        }

        // Whenever the egg is changed, set the server's startup command to the egg's default.
        setFieldValue('image', Object.values(egg.dockerImages)[0] ?? '');
        setFieldValue('startup', '');
    }, [egg]);

    return (
        <AdminBox title={'Startup Command'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting} />

            <div css={tw`mb-6`}>
                <Field
                    id={'startup'}
                    name={'startup'}
                    label={'Startup Command'}
                    type={'text'}
                    description={
                        "Edit your server's startup command here. The following variables are available by default: {{SERVER_MEMORY}}, {{SERVER_IP}}, and {{SERVER_PORT}}."
                    }
                    placeholder={egg?.startup || ''}
                />
            </div>

            <div>
                <Label>Default Startup Command</Label>
                <Input value={egg?.startup || ''} readOnly />
            </div>
        </AdminBox>
    );
}

export function ServerServiceContainer({
    egg,
    setEgg,
    nestId: _nestId,
}: {
    egg: Egg | null;
    setEgg: (value: Egg | null) => void;
    nestId: number;
}) {
    const { isSubmitting } = useFormikContext();

    const [nestId, setNestId] = useState<number>(_nestId);

    return (
        <AdminBox title={'Service Configuration'} isLoading={isSubmitting} css={tw`w-full`}>
            <div css={tw`mb-6`}>
                <NestSelector selectedNestId={nestId} onNestSelect={setNestId} />
            </div>
            <div css={tw`mb-6`}>
                <EggSelect nestId={nestId} selectedEggId={egg?.id} onEggSelect={setEgg} />
            </div>
            <div css={tw`bg-neutral-800 border border-neutral-900 shadow-inner p-4 rounded`}>
                <FormikSwitch name={'skipScripts'} label={'Skip Egg Install Script'} description={'Soon™'} />
            </div>
        </AdminBox>
    );
}

export function ServerImageContainer() {
    const { isSubmitting } = useFormikContext();

    return (
        <AdminBox title={'Image Configuration'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting} />

            <div css={tw`md:w-full md:flex md:flex-col`}>
                <div>
                    {/* TODO: make this a proper select but allow a custom image to be specified if needed. */}
                    <Field id={'image'} name={'image'} label={'Docker Image'} type={'text'} />
                </div>
            </div>
        </AdminBox>
    );
}

export function ServerVariableContainer({ variable, value }: { variable: EggVariable; value?: string }) {
    const key = 'environment.' + variable.environmentVariable;

    const [, , { setValue, setTouched }] = useField<string | undefined>(key);

    const { isSubmitting } = useFormikContext();

    useEffect(() => {
        if (value === undefined) {
            return;
        }

        setValue(value);
        setTouched(true);
    }, [value]);

    return (
        <AdminBox css={tw`relative w-full`} title={<p css={tw`text-sm uppercase`}>{variable.name}</p>}>
            <SpinnerOverlay visible={isSubmitting} />

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

function ServerStartupForm({
    egg,
    setEgg,
    server,
}: {
    egg: Egg | null;
    setEgg: (value: Egg | null) => void;
    server: Server;
}) {
    const {
        isSubmitting,
        isValid,
        values: { environment },
    } = useFormikContext<Values>();

    return (
        <Form>
            <div css={tw`flex flex-col mb-16`}>
                <div css={tw`flex flex-row mb-6`}>
                    <ServerStartupLineContainer egg={egg} server={server} />
                </div>

                <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6 mb-6`}>
                    <div css={tw`flex`}>
                        <ServerServiceContainer egg={egg} setEgg={setEgg} nestId={server.nestId} />
                    </div>

                    <div css={tw`flex`}>
                        <ServerImageContainer />
                    </div>
                </div>

                <div css={tw`grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8`}>
                    {/* This ensures that no variables are rendered unless the environment has a value for the variable. */}
                    {egg?.relationships.variables
                        ?.filter(v => Object.keys(environment).find(e => e === v.environmentVariable) !== undefined)
                        .map((v, i) => (
                            <ServerVariableContainer
                                key={i}
                                variable={v}
                                value={
                                    server.relationships.variables?.find(
                                        v2 => v.eggId === v2.eggId && v.environmentVariable === v2.environmentVariable,
                                    )?.serverValue
                                }
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
    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );
    const [egg, setEgg] = useState<InferModel<typeof getEgg> | null>(null);

    useEffect(() => {
        if (!server) return;

        getEgg(server.eggId)
            .then(egg => setEgg(egg))
            .catch(error => console.error(error));
    }, [server?.eggId]);

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
                startup: server.container.startup || '',
                environment: [] as Record<string, any>,
                image: server.container.image,
                eggId: server.eggId,
                skipScripts: false,
            }}
            validationSchema={object().shape({})}
        >
            <ServerStartupForm
                egg={egg}
                // @ts-expect-error fix this
                setEgg={setEgg}
                server={server}
            />
        </Formik>
    );
};
