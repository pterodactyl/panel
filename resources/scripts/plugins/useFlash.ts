import { Actions } from 'easy-peasy';
import { FlashStore } from '@/state/flashes';
import { useStoreActions } from '@/state/hooks';

const useFlash = (): Actions<FlashStore> => {
    return useStoreActions(actions => actions.flashes);
};

interface KeyedFlashStore {
    clearFlashes: () => void;
    clearAndAddHttpError: (error?: Error | string | null) => void;
}

const useFlashKey = (key: string): KeyedFlashStore => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    return {
        clearFlashes: () => clearFlashes(key),
        clearAndAddHttpError: (error) => clearAndAddHttpError({ key, error }),
    };
};

export { useFlashKey };
export default useFlash;
