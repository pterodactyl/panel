import { useStoreState } from '@/state/hooks';

export default (context: string | string[]) => {
    const key = Array.isArray(context) ? context.join(':') : context;
    const uuid = useStoreState(state => state.user.data?.uuid);

    if (!key.trim().length) {
        throw new Error('Must provide a valid context key to "useUserSWRContextKey".');
    }

    return `swr::${uuid || 'unknown'}:${key.trim()}`;
};
