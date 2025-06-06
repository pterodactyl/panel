import React, { useContext, useEffect } from 'react';
import { Hook } from '@/api/server/hooks/getServerHooks';
import Field from '@/components/elements/Field';
import { Form, Formik, FormikHelpers } from 'formik';
import FormikSwitch from '@/components/elements/FormikSwitch';
import createOrUpdateHook from '@/api/server/hooks/createOrUpdateHook';
import { ServerContext } from '@/state/server';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import { Button } from '@/components/elements/button/index';
import ModalContext from '@/context/ModalContext';
import asModal from '@/hoc/asModal';
import Select from '@/components/elements/Select';
import Label from '@/components/elements/Label';
//import Switch from '@/components/elements/Switch';

interface Props {
    hook?: Hook;
}

interface Values {
    name: string;
    enabled: boolean;
}

const EditHookModal = ({ hook }: Props) => {
    const { addError, clearFlashes } = useFlash();
    const { dismiss } = useContext(ModalContext);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const appendHook = ServerContext.useStoreActions((actions) => actions.hooks!.appendHook);
    const triggerDefinitions = ServerContext.useStoreState((state) => state.hooks!.trigger_definitions);

    useEffect(() => {
        return () => {
            clearFlashes('hook:edit');
        };
    }, []);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('hook:edit');
        createOrUpdateHook(uuid, {
            name: values.name,
            enabled: values.enabled,
            id: hook?.id,
        })
            .then((hook) => {
                setSubmitting(false);
                appendHook(hook);
                dismiss();
            })
            .catch((error) => {
                console.error(error);

                setSubmitting(false);
                addError({ key: 'hook:edit', message: httpErrorToHuman(error) });
            });
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={
                {
                    name: hook?.name || '',
                    enabled: hook?.enabled ?? true,
                } as Values
            }
        >
            {({ isSubmitting }) => (
                <Form>
                    <h3 css={tw`text-2xl mb-6`}>{hook ? 'Edit hook' : 'Create new hook'}</h3>
                    <FlashMessageRender byKey={'hook:edit'} css={tw`mb-6`} />
                    <Field
                        name={'name'}
                        label={'Hook name'}
                        description={'A human readable identifier for this hook.'}
                    />
                    <div css={tw`mt-6`}>
                        <Label htmlFor={'trigger'} isLight={false}>
                            Trigger Event
                        </Label>
                        <Select className={'trigger'}>
                            {triggerDefinitions.map((trigger) => (
                                <option value={trigger.key} key={trigger.key}>
                                    {trigger.name}
                                </option>
                            ))}
                        </Select>
                        <p className={'input-help'}>Choose the event that will cause this hook to activate.</p>
                    </div>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'enabled'}
                            description={'This hook will be executed automatically if enabled.'}
                            label={'Hook Enabled'}
                        />
                    </div>
                    <div css={tw`mt-6 text-right`}>
                        <Button className={'w-full sm:w-auto'} type={'submit'} disabled={isSubmitting}>
                            {hook ? 'Save changes' : 'Create hook'}
                        </Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

export default asModal<Props>()(EditHookModal);
