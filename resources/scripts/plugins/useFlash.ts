import { Actions, useStoreActions } from 'easy-peasy';
import { FlashStore } from '@/state/flashes';
import { ApplicationStore } from '@/state';

interface KeyedFlashStore {
    clearFlashes: () => void;
    clearAndAddHttpError: (error?: Error | string | null) => void;
}

const useFlash = (): Actions<FlashStore> => {
    return useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
};

const useFlashKey = (key: string): KeyedFlashStore => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    return {
        clearFlashes: () => clearFlashes(key),
        clearAndAddHttpError: (error) => clearAndAddHttpError({ key, error }),
    };
};

export { useFlashKey };
export default useFlash;
