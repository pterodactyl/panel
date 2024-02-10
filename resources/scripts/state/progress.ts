import { action, Action } from 'easy-peasy';

export interface ProgressStore {
    continuous: boolean;
    progress?: number;

    startContinuous: Action<ProgressStore>;
    setProgress: Action<ProgressStore, number | undefined>;
    setComplete: Action<ProgressStore>;
}

const progress: ProgressStore = {
    continuous: false,
    progress: undefined,

    startContinuous: action(state => {
        state.continuous = true;
    }),

    setProgress: action((state, payload) => {
        state.progress = payload;
    }),

    setComplete: action(state => {
        if (state.progress) {
            state.progress = 100;
        }

        state.continuous = false;
    }),
};

export default progress;
