// noinspection ES6UnusedImports
import { ApplicationStore } from '@/state';
// eslint-disable-next-line @typescript-eslint/no-unused-vars
import EasyPeasy, { Actions, State } from 'easy-peasy';

declare module 'easy-peasy' {
    export function useStoreState<Result>(mapState: (state: State<ApplicationStore>) => Result): Result;

    export function useStoreActions<Result>(mapActions: (actions: Actions<ApplicationStore>) => Result): Result;
}
