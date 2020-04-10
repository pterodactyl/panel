// noinspection ES6UnusedImports
import EasyPeasy, { Actions, State } from 'easy-peasy';
import { ApplicationStore } from '@/state';

declare module 'easy-peasy' {
    export function useStoreState<Result>(
        mapState: (state: State<ApplicationStore>) => Result,
    ): Result;

    export function useStoreActions<Result>(
        mapActions: (actions: Actions<ApplicationStore>) => Result,
    ): Result;
}
