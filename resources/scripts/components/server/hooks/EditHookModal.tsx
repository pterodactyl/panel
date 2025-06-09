import React, { useContext, useEffect, useState } from 'react';
import { Hook } from '@/api/server/hooks/getServerHooks';
import Field from '@/components/elements/Field';
import { Form, Formik, FormikHelpers, useFormikContext} from 'formik';
import FormikSwitch from '@/components/elements/FormikSwitch';
import createOrUpdateHook from '@/api/server/hooks/createOrUpdateHook';
import { TriggerDefinition } from '@/api/server/hooks/getTriggerDefinitions';
import { ActionDefinition } from '@/api/server/hooks/getActionDefinitions';
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
    const actionDefinitions = ServerContext.useStoreState((state) => state.hooks!.action_definitions);
    const [selectedTrigger, setSelectedTrigger] = useState<TriggerDefinition | null>(null);
    const [selectedAction, setSelectedAction] = useState<ActionDefinition | null>(null);
    const { setFieldValue, values } = useFormikContext<any>();

    useEffect(() => {
        return () => {
            clearFlashes('hook:edit');
        };
    }, []);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('hook:edit');
        console.log(values);
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
                    triggers: hook?.triggers || [],
                    actions: hook?.actions || [],
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
                        <Select
                            className={'trigger'}
                            onChange={(e) => {
                                const selected = triggerDefinitions.find((t) => t.key === e.target.value);
                                if (!selected) {
                                    addError({ key: 'hook:edit', message: 'You must select a valid trigger.' });
                                }
                                setFieldValue('trigger', selected!.key);
                                setSelectedTrigger(selected || null);
                            }}
                        >
                            <option value=''>-- Select an Trigger --</option>
                            {triggerDefinitions.map((trigger, key) => (
                                <option value={trigger.key} key={key}>
                                    {trigger.name}
                                </option>
                            ))}
                        </Select>
                        <p className={'input-help mt-1 text-xs'}>
                            Choose the event that will cause this hook to activate.
                        </p>
                    </div>
                    {selectedTrigger &&
                        selectedTrigger.config_schema.map((trigger, key) =>
                            trigger.input === 'dropdown' ? (
                                <div css={tw`mt-6`} key={key}>
                                    <Label htmlFor={trigger.label.toLowerCase().replace(' ', '')} isLight={false}>
                                        Trigger Event
                                    </Label>
                                    <Select
                                        name={trigger.label.toLowerCase().replace(' ', '')}
                                        className={trigger.label.toLowerCase().replace(' ', '')}
                                    >
                                        {Object.entries(trigger.options || {}).map(([key, label]) => (
                                            <option value={key} key={key}>
                                                {label}
                                            </option>
                                        ))}
                                    </Select>
                                    <p className={'input-help mt-1 text-xs'}>{selectedTrigger.description}</p>
                                </div>
                            ) : trigger.input === 'text' ? (
                                <div css={tw`mt-6`} key={key}>
                                    <Field
                                        name={trigger.label.toLowerCase().replace(' ', '')}
                                        label={trigger.label}
                                        description={''}
                                    />
                                </div>
                            ) : trigger.input === 'number' ? (
                                <div css={tw`mt-6`} key={key}>
                                    <Field
                                        type={'number'}
                                        name={trigger.label.toLowerCase().replace(' ', '')}
                                        label={trigger.label}
                                        description={''}
                                    />
                                </div>
                            ) : null
                        )}

                    <div css={tw`mt-6`}>
                        <Label htmlFor={'action'} isLight={false}>
                            Action
                        </Label>
                        <Select
                            name={'action'}
                            className={'action'}
                            onChange={(e) => {
                                const selected = actionDefinitions.find((t) => t.key === e.target.value);
                                if (!selected) {
                                    addError({ key: 'hook:edit', message: 'You must select a valid action.' });
                                }
                                setSelectedAction(selected || null);
                            }}
                        >
                            <option value=''>-- Select an Action --</option>
                            {actionDefinitions.map((action, key) => (
                                <option value={action.key} key={key}>
                                    {action.name}
                                </option>
                            ))}
                        </Select>
                        <p className={'input-help mt-1 text-xs'}>Choose the action that will run.</p>
                    </div>

                    {selectedAction &&
                        selectedAction.config_schema.map((action, key) =>
                            action.input === 'dropdown' ? (
                                <div css={tw`mt-6`} key={key}>
                                    <Label htmlFor={action.label.toLowerCase().replace(' ', '')} isLight={false}>
                                        Action
                                    </Label>
                                    <Select
                                        name={action.label.toLowerCase().replace(' ', '')}
                                        className={action.label.toLowerCase().replace(' ', '')}
                                    >
                                        {Object.entries(action.options || {}).map(([key, label]) => (
                                            <option value={key} key={key}>
                                                {label}
                                            </option>
                                        ))}
                                    </Select>
                                    <p className={'input-help mt-1 text-xs'}>{selectedAction.description}</p>
                                </div>
                            ) : action.input === 'text' ? (
                                <div css={tw`mt-6`} key={key}>
                                    <Field
                                        name={action.label.toLowerCase().replace(' ', '')}
                                        label={action.label}
                                        description={''}
                                    />
                                </div>
                            ) : action.input === 'number' ? (
                                <div css={tw`mt-6`} key={key}>
                                    <Field
                                        type={'number'}
                                        name={action.label.toLowerCase().replace(' ', '')}
                                        label={action.label}
                                        description={''}
                                    />
                                </div>
                            ) : null
                        )}

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
