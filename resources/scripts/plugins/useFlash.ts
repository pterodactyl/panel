import { Actions, useStoreActions } from 'easy-peasy';
import { FlashStore } from '@/state/flashes';
import { ApplicationStore } from '@/state';

const useFlash = (): Actions<FlashStore> => {
    return useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
};

export default useFlash;
