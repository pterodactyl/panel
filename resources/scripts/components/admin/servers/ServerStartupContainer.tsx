import React from 'react';
import Button from '@/components/elements/Button';
import FormikSwitch from '@/components/elements/FormikSwitch';
import Input from '@/components/elements/Input';
import AdminBox from '@/components/admin/AdminBox';
import tw from 'twin.macro';
import { object } from 'yup';
import updateServer from '@/api/admin/servers/updateServer';
import Field from '@/components/elements/Field';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { Context } from '@/components/admin/servers/ServerRouter';
import { ApplicationStore } from '@/state';
import { Actions, useStoreActions } from 'easy-peasy';
import Label from '@/components/elements/Label';
// import { ServerEggVariable } from '@/api/server/types';

/* interface Props {
  variable: ServerEggVariable;
} */

interface Values {
    startupCommand: string;
    nestId: number;
    eggId: number;
}

/* const VariableBox = ({ variable }: Props) => {
    const { isSubmitting } = useFormikContext();

    const server = Context.useStoreState(state => state.server);

    if (server === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Service Configuration'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Form css={tw`mb-0`}>
                <div css={tw`md:w-full md:flex md:flex-col`}>
                    <Field
                        name={variable.envVariable}
                        defaultValue={variable.serverValue}
                        placeholder={variable.defaultValue}
                        description={variable.description}
                    />
                </div>
            </Form>
        </AdminBox>
    );
}; */

const ServerServiceContainer = () => {
    const { isSubmitting } = useFormikContext();

    const server = Context.useStoreState(state => state.server);

    if (server === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Service Configuration'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Form css={tw`mb-0`}>
                <div css={tw`md:w-full md:flex md:flex-col`}>
                    <div css={tw`flex-1`}>
                        <div css={tw`p-3 mb-6 border-l-4 border-red-500`}>
                            <p css={tw`text-xs text-neutral-200`}>
                                This is a destructive operation in many cases. This server will be stopped immediately in order for this action to proceed.
                            </p>
                        </div>
                        <div css={tw`p-3 mb-6 border-l-4 border-red-500`}>
                            <p css={tw`text-xs text-neutral-200`}>
                                Changing any of the below values will result in the server processing a re-install command. The server will be stopped and will then proceed. If you would like the service scripts to not run, ensure the box is checked at the bottom.
                            </p>
                        </div>
                    </div>
                    <div css={tw`pb-4 mb-6 md:w-full md:flex md:flex-col md:mb-0`}>
                        Nest/Egg Selector HERE
                    </div>
                    <div css={tw`pb-4 mb-6 md:w-full md:flex md:flex-col md:mb-0`}>
                        <div css={tw`mt-6 bg-neutral-800 border border-neutral-900 shadow-inner p-4 rounded`}>
                            <FormikSwitch
                                name={'skip_install_script'}
                                label={'Skip Egg Install Script'}
                                description={'If the selected Egg has an install script attached to it, the script will run during install. If you would like to skip this step, check this box.'}
                            />
                        </div>
                    </div>
                </div>
            </Form>
        </AdminBox>
    );
};

const ServerStartupContainer = () => {
    const { isSubmitting } = useFormikContext();

    const server = Context.useStoreState(state => state.server);

    if (server === undefined) {
        return (
            <></>
        );
    }

    return (
        <AdminBox title={'Startup Command'} css={tw`relative w-full`}>
            <SpinnerOverlay visible={isSubmitting}/>

            <Form css={tw`mb-0`}>
                <div css={tw`mb-6 md:w-full md:flex md:flex-col`}>
                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mr-4`}>
                        <Field
                            id={'startupCommand'}
                            name={'startupCommand'}
                            label={'Startup Command'}
                            type={'string'}
                            description={'Edit your server\'s startup command here. The following variables are available by default: {{SERVER_MEMORY}}, {{SERVER_IP}}, and {{SERVER_PORT}}.'}
                        />
                    </div>

                    <div css={tw`mb-6 md:w-full md:flex md:flex-col md:mb-0`}>
                        <div>
                            <Label>Default Startup Command</Label>
                            <Input
                                disabled
                                value={server.relations.egg?.configStartup || ''}
                            />
                        </div>
                    </div>
                </div>
            </Form>
        </AdminBox>
    );
};

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const server = Context.useStoreState(state => state.server);
    const setServer = Context.useStoreActions(actions => actions.setServer);

    if (server === undefined) {
        return (
            <></>
        );
    }

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('server');

        // updateServer(server.id, values)
        //     .then(() => setServer({ ...server, ...values }))
        //     .catch(error => {
        //         console.error(error);
        //         clearAndAddHttpError({ key: 'server', error });
        //     })
        //     .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                startupCommand: server.container.startupCommand,
                nestId: server.nestId,
                eggId: server.eggId,
            }}
            validationSchema={object().shape({
            })}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <div css={tw`flex flex-col`}>
                        <div css={tw`flex flex-col w-full mb-4 mr-0 lg:mr-2`}>
                            <ServerStartupContainer/>
                        </div>
                        <div css={tw`flex flex-col w-1/2 mr-0 lg:mr-2`}>
                            <ServerServiceContainer/>
                        </div>
                        <div css={tw`flex flex-col w-1/2 mr-0 lg:mr-2`}>
                            Server Startup variables go here
                        </div>
                        <div css={tw`py-2 pr-6 mt-4 rounded shadow-md bg-neutral-700`}>
                            <div css={tw`flex flex-row`}>
                                <Button type="submit" size="small" css={tw`ml-auto`} disabled={isSubmitting || !isValid}>
                                    Save Changes
                                </Button>
                            </div>
                        </div>
                    </div>
                )
            }
        </Formik>
    );
};
