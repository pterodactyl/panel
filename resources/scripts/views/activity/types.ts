import type { ClientActivityLogAttributes } from '@/api/extensionTypes';
import type { CorePanelDestination } from '@/sdk/navigation';

/** Whose activity the log shows: the signed-in account, the current server, or the admin area. */
export type ActivityScope = 'account' | 'server' | 'admin';
/** An event's properties, which its `activity` translation reads. */
export type ActivityProperties = ClientActivityLogAttributes['properties'];

export interface ActivityEntry {
    readonly id: string;
    /** For example `server:power.start`. */
    readonly event: string;
    /** Null for actions the panel took on its own. */
    readonly actor: { readonly username: string | null; readonly email: string | null } | null;
    readonly properties: ActivityProperties;
    /** The properties hold more than the description shows. */
    readonly hasMetadata: boolean;
    readonly ip: string | null;
    readonly timestamp: string;
    /** Done with an API key. */
    readonly api: boolean;
    /** Done over SFTP. */
    readonly sftp: boolean;
    readonly userAgent: string | null;
    /** The log showing only this entry's event. */
    readonly filter: CorePanelDestination;
}
export interface ActivityFilterOption {
    readonly value: string;
    readonly label: string;
    /** The option's label where its group is not shown, such as the selected value. */
    readonly textLabel?: string;
}
export interface ActivityEventGroup {
    readonly label: string;
    readonly options: readonly ActivityFilterOption[];
}
export interface ActivityLogPagination {
    readonly total: number;
    readonly count: number;
    readonly perPage: number;
    readonly currentPage: number;
    readonly totalPages: number;
}
/** A page of the log, newest first, filtered through the URL so the filters survive a reload. */
export interface ActivityLogModel {
    readonly scope: ActivityScope;
    readonly entries: readonly ActivityEntry[];
    /** Nothing has loaded yet. */
    readonly loading: boolean;
    /** The last load failed; the page loaded before it, if any, still shows. */
    readonly error: string | null;
    readonly retry: () => void;
    /** Null until a page loads. */
    readonly pagination: ActivityLogPagination | null;
    readonly event: string | null;
    readonly user: string | null;
    /** Recorded events grouped by resource, with readable labels. */
    readonly events: readonly ActivityEventGroup[];
    /** The users who acted here; null where the log only holds the signed-in user's actions. */
    readonly users: readonly ActivityFilterOption[] | null;
    readonly actions: {
        readonly setEvent: (event: string | null) => void;
        readonly setUser: (user: string | null) => void;
        readonly clearFilters: () => void;
        readonly setPage: (page: number) => void;
    };
}
/** The activity pages are compositions of the log. */
export type ActivityPageModel = Readonly<Record<never, never>>;

declare module '../registry' {
    interface ViewModels {
        'activity.log': ActivityLogModel;
        'page.account.activity': ActivityPageModel;
        'page.server.activity': ActivityPageModel;
    }
    interface ViewInputs {
        'activity.log': { readonly scope: ActivityScope };
    }
}
