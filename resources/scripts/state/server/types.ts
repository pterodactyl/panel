import type { ServerStore } from '@/state/server';
import type { StoreApi } from 'zustand/vanilla';

export type ServerSet = StoreApi<ServerStore>['setState'];
export type ServerGet = StoreApi<ServerStore>['getState'];
