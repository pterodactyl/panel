// noinspection ES6UnusedImports
// eslint-disable-next-line @typescript-eslint/no-unused-vars
import EasyPeasy, { Actions, State } from 'easy-peasy';
import { ApplicationStore } from '@/state';

declare module 'easy-peasy' {
    export function useStoreState<Result>(mapState: (state: State<ApplicationStore>) => Result): Result;

    export function useStoreActions<Result>(mapActions: (actions: Actions<ApplicationStore>) => Result): Result;
}
