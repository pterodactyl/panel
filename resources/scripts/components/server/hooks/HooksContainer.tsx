import React, { useEffect, useState } from 'react';
import getServerHooks from '@/api/server/hooks/getServerHooks';
import getTriggerDefinitions from '@/api/server/hooks/getTriggerDefinitions';
import getActionDefinitions from '@/api/server/hooks/getActionDefinitions';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import { useHistory, useRouteMatch } from 'react-router-dom';
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

export default () => {
    const match = useRouteMatch();
    const history = useHistory();

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearFlashes, addError } = useFlash();
    const [loading, setLoading] = useState(true);
    const [visible, setVisible] = useState(false);

    const hooks = ServerContext.useStoreState((state) => state.hooks.data);
    const setHooks = ServerContext.useStoreActions((actions) => actions.hooks.setHooks);
    const setTriggerDefinitions = ServerContext.useStoreActions((actions) => actions.hooks.setTriggerDefinitions);
    const setActionDefinitions = ServerContext.useStoreActions((actions) => actions.hooks.setActionDefinitions);
    const triggerDefinitions = ServerContext.useStoreState((state) => state.hooks!.trigger_definitions);
    const actionDefinitions = ServerContext.useStoreState((state) => state.hooks!.action_definitions);

    useEffect(() => {
        clearFlashes('hooks');
        getServerHooks(uuid)
            .then((hooks) => setHooks(hooks))
            .catch((error) => {
                addError({ message: httpErrorToHuman(error), key: 'hooks' });
                console.error(error);
            })
            .then(() => setLoading(false));
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
                                href={`${match.url}/${hook.id}`}
                                css={tw`cursor-pointer mb-2 flex-wrap`}
                                onClick={(e: any) => {
                                    e.preventDefault();
                                    history.push(`${match.url}/${hook.id}`);
                                }}
                            >
                                <HookRow
                                    hook={hook}
                                    triggerDefinition={triggerDefinitions.find((t) => t.key === hook.trigger!.type)}
                                    actionDefinition={actionDefinitions.find((t) => t.key === hook.action!.type)}
                                />
                            </GreyRowBox>
                        ))
                    )}
                    <Can action={'hooks.create'}>
                        <div css={tw`mt-8 flex justify-end`}>
                            <EditHookModal visible={visible} onModalDismissed={() => setVisible(false)} />
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
