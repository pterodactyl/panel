import { UserState } from '@/state/types';
import { action } from 'easy-peasy';

const user: UserState = {
    data: undefined,
    setUserData: action((state, payload) => {
        state.data = payload;
    }),
};

export default user;
