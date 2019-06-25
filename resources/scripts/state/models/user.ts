import { Action, action, Thunk, thunk } from 'easy-peasy';
import updateAccountEmail from '@/api/account/updateAccountEmail';

export interface UserData {
    uuid: string;
    username: string;
    email: string;
    language: string;
    rootAdmin: boolean;
    useTotp: boolean;
    createdAt: Date;
    updatedAt: Date;
}

export interface UserState {
    data?: UserData;
    setUserData: Action<UserState, UserData>;
    updateUserData: Action<UserState, Partial<UserData>>;
    updateUserEmail: Thunk<UserState, { email: string; password: string }, any, {}, Promise<void>>;
}

const user: UserState = {
    data: undefined,
    setUserData: action((state, payload) => {
        state.data = payload;
    }),

    updateUserData: action((state, payload) => {
        // Limitation of Typescript, can't do much about that currently unfortunately.
        // @ts-ignore
        state.data = { ...state.data, ...payload };
    }),

    updateUserEmail: thunk(async (actions, payload) => {
        await updateAccountEmail(payload.email, payload.password);

        actions.updateUserData({ email: payload.email });
    }),
};

export default user;
