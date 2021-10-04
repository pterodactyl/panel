import { useStoreState } from 'easy-peasy';
import { usePersistedState } from '@/plugins/usePersistedState';
import { Dispatch, SetStateAction } from 'react';

export default <S extends any = undefined>(key: string, defaultValue: S): [ S, Dispatch<SetStateAction<S>> ] => {
    const uuid = useStoreState(state => state.user.data!.uuid);

    return usePersistedState(`${uuid}:${key}`, defaultValue);
};
