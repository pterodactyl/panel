import { action, Action } from 'easy-peasy';
import { Hook } from '@/api/server/hooks/getServerHooks';
import { TriggerDefinition } from '@/api/server/hooks/getTriggerDefinitions';
import { ActionDefinition } from '@/api/server/hooks/getActionDefinitions';

export interface ServerHookStore {
    data: Hook[];
    trigger_definitions: TriggerDefinition[];
    action_definitions: ActionDefinition[];
    setActionDefinitions: Action<ServerHookStore, ActionDefinition[]>;
    setTriggerDefinitions: Action<ServerHookStore, TriggerDefinition[]>;
    setHooks: Action<ServerHookStore, Hook[]>;
    appendHook: Action<ServerHookStore, Hook>;
    removeHook: Action<ServerHookStore, number>;
}

const hooks: ServerHookStore = {
    data: [],
    trigger_definitions: [],
    action_definitions: [],
    setActionDefinitions: action((state, payload) => {
        state.action_definitions = payload;
    }),
    setTriggerDefinitions: action((state, payload) => {
        state.trigger_definitions = payload;
    }),
    setHooks: action((state, payload) => {
        state.data = payload;
    }),
    appendHook: action((state, payload) => {
        if (state.data.find((hook) => hook.id === payload.id)) {
            state.data = state.data.map((hook) => (hook.id === payload.id ? payload : hook));
        } else {
            state.data = [...state.data, payload];
        }
    }),
    removeHook: action((state, payload) => {
        state.data = [...state.data.filter((hook) => hook.id !== payload)];
    }),
};

export default hooks;
