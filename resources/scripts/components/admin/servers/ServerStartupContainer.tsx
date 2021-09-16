import getEgg, { Egg, EggVariable } from '@/api/admin/eggs/getEgg';
import { Server } from '@/api/admin/servers/getServers';
import EggSelect from '@/components/admin/servers/EggSelect';
import NestSelect from '@/components/admin/servers/NestSelect';
import FormikSwitch from '@/components/elements/FormikSwitch';
import InputSpinner from '@/components/elements/InputSpinner';
import React, { useEffect, useState } from 'react';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';
import { object } from 'yup';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, useFormikContext } from 'formik';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import Label from '@/components/elements/Label';
import TitledGreyBox from '@/components/elements/TitledGreyBox';

// interface Values {
//     startupCommand: string;
//     image: string;
//
//     eggId: number;
//     skipScripts: boolean;
// }

function ServerStartupLineContainer ({ egg }: { egg: Egg }) {
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
                    <Input value={egg.startup} readOnly/>
                </div>
            </Form>
        </AdminBox>
    );
}

function ServerServiceContainer ({ server, egg, setEgg }: { server: Server, egg: Egg | null, setEgg: (value: Egg | null) => void }) {
    const { isSubmitting } = useFormikContext();

    const [ nestId, setNestId ] = useState<number | null>(server.nestId);

    return (
        <AdminBox title={'Service Configuration'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Form css={tw`mb-0`}>
                <div css={tw`mb-6`}>
                    <NestSelect nestId={nestId} setNestId={setNestId}/>
                </div>

                <div css={tw`mb-6`}>
                    <EggSelect nestId={nestId} egg={egg} setEgg={setEgg}/>
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

function ServerVariableContainer ({ variable, defaultValue }: { variable: EggVariable, defaultValue: string }) {
    const [ value, setValue ] = useState<string>('');

    useEffect(() => {
        setValue(defaultValue);
    }, [ defaultValue ]);

    return (
        <AdminBox title={<p css={tw`text-sm uppercase`}>{variable.name}</p>}>
            <InputSpinner visible={false}>
                <Input
                    name={variable.envVariable}
                    placeholder={variable.defaultValue}
                    type={'text'}
                    value={value}
                    onChange={e => setValue(e.target.value)}
                />
            </InputSpinner>
            <p css={tw`mt-1 text-xs text-neutral-300`}>
                {variable.description}
            </p>
        </AdminBox>
    );
}

export default function ServerStartupContainer ({ server }: { server: Server }) {
    const { clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const [ egg, setEgg ] = useState<Egg | null>(null);

    useEffect(() => {
        getEgg(server.eggId, [ 'variables' ])
            .then(egg => setEgg(egg))
            .catch(error => console.error(error));
    }, []);

    if (egg === null) {
        return (<></>);
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
                    <div css={tw`flex flex-row mb-6`}>
                        <ServerStartupLineContainer egg={egg}/>
                    </div>

                    <div css={tw`grid grid-cols-1 md:grid-cols-2 md:gap-x-8 gap-y-6 md:gap-y-0 mb-6`}>
                        <div css={tw`flex`}>
                            <ServerServiceContainer
                                server={server}
                                egg={egg}
                                setEgg={setEgg}
                            />
                        </div>
                        <div css={tw`flex`}>
                            <ServerImageContainer/>
                        </div>
                    </div>

                    {egg !== null &&
                    <div css={tw`grid gap-y-6 gap-x-8 grid-cols-1 md:grid-cols-2`}>
                        {egg.relations.variables?.map((v, i) => (
                            <ServerVariableContainer
                                key={i}
                                variable={v}
                                defaultValue={server.relations?.variables.find(v2 => v.eggId === v2.eggId && v.envVariable === v2.envVariable)?.serverValue || ''}
                            />
                        ))}
                    </div>
                    }

                    <div css={tw`bg-neutral-700 rounded shadow-md py-2 pr-6 mt-6`}>
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
