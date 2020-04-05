// noinspection ES6UnusedImports
import EasyPeasy from 'easy-peasy';
import { ApplicationStore } from '@/state';

declare module 'easy-peasy' {
    export function useStoreState<Result>(
        mapState: (state: ApplicationStore) => Result,
    ): Result;
}
