import React, { useEffect, useState } from 'react';
import getServerHooks, { Hook } from '@/api/server/hooks/getServerHooks';
import getTriggerDefinitions from '@/api/server/hooks/getTriggerDefinitions';
import getActionDefinitions from '@/api/server/hooks/getActionDefinitions';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import HookRow from '@/components/server/hooks/HookRow';
import { httpErrorToHuman } from '@/api/http';
import EditHookModal from '@/components/server/hooks/EditHookModal';
import Can from '@/components/elements/Can';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import { Button } from '@/components/elements/button/index';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import getServerSchedules from '@/api/server/schedules/getServerSchedules';

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearFlashes, addError } = useFlash();
    const [loading, setLoading] = useState(true);
    const [visible, setVisible] = useState(false);
    const [selectedHook, setSelectedHook] = useState<Hook | null>(null);

    const hooks = ServerContext.useStoreState((state) => state.hooks.data);
    const setHooks = ServerContext.useStoreActions((actions) => actions.hooks.setHooks);
    const setTriggerDefinitions = ServerContext.useStoreActions((actions) => actions.hooks.setTriggerDefinitions);
    const setActionDefinitions = ServerContext.useStoreActions((actions) => actions.hooks.setActionDefinitions);
    const setSchedules = ServerContext.useStoreActions((actions) => actions.schedules.setSchedules);
    const triggerDefinition = ServerContext.useStoreState((state) => state.hooks!.trigger_definitions);
    const actionDefinition = ServerContext.useStoreState((state) => state.hooks!.action_definitions);
    useEffect(() => {
        clearFlashes('hooks');
        getServerHooks(uuid)
            .then((hooks) => setHooks(hooks))
            .catch((error) => {
                addError({ message: httpErrorToHuman(error), key: 'hooks' });
                console.error(error);
            })
            .then(() => setLoading(false));
        getServerSchedules(uuid)
            .then((schedules) => setSchedules(schedules))
            .catch((error) => {
                addError({ message: httpErrorToHuman(error), key: 'hooks' });
                console.error(error);
            });
        clearFlashes('trigger_definitions');
        getTriggerDefinitions(uuid)
            .then((triggerDefinitions) => setTriggerDefinitions(triggerDefinitions))
            .catch((error) => {
                addError({ message: httpErrorToHuman(error), key: 'hooks' });
            });
        getActionDefinitions(uuid)
            .then((actionDefinitions) => setActionDefinitions(actionDefinitions))
            .catch((error) => {
                addError({ message: httpErrorToHuman(error), key: 'hooks' });
            });
    }, []);
    return (
        <ServerContentBlock title={'Hooks'}>
            <FlashMessageRender byKey={'hooks'} css={tw`mb-4`} />
            <EditHookModal
                visible={visible}
                onModalDismissed={() => {
                    setVisible(false);
                    setSelectedHook(null);
                }}
                hook={selectedHook}
            />
            {!hooks.length && loading ? (
                <Spinner size={'large'} centered />
            ) : (
                <>
                    {hooks.length === 0 ? (
                        <p css={tw`text-sm text-center text-neutral-300`}>
                            There are no hooks configured for this server.
                        </p>
                    ) : (
                        hooks.map((hook) => (
                            <GreyRowBox
                                as={'a'}
                                key={hook.id}
                                css={tw`cursor-pointer mb-2 flex-wrap`}
                                onClick={(e: any) => {
                                    e.preventDefault();
                                    setVisible(true);
                                    setSelectedHook(hook);
                                }}
                            >
                                <HookRow
                                    hook={hook}
                                    triggerDefinition={triggerDefinition.find((t) => t.key === hook.trigger!.type)}
                                    actionDefinition={actionDefinition.find((t) => t.key === hook.action!.type)}
                                />
                            </GreyRowBox>
                        ))
                    )}
                    <Can action={'hooks.create'}>
                        <div css={tw`mt-8 flex justify-end`}>
                            <Button type={'button'} onClick={() => setVisible(true)}>
                                Create hook
                            </Button>
                        </div>
                    </Can>
                </>
            )}
        </ServerContentBlock>
    );
};
