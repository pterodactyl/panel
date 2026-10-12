// The model of every engine view, published with the SDK so `ViewProps<'name'>` resolves for extensions and themes.
// Areas with many views publish theirs from their own module in sdk/models.
export type {
    DashboardModel,
    DashboardScope,
    ServerCardModel,
    ServerCardPermissions,
    ServerCardState,
    ServerListItem,
    ServerListModel,
    ServerListPagination,
    ServerListTotals,
    ServerUnavailableReason,
} from './dashboard';
export type {
    AccountArea,
    AccountLayoutModel,
    AdminArea,
    AdminLayoutModel,
    AppArea,
    AppLayoutLink,
    AppLayoutModel,
    AuthLayoutModel,
    DashboardArea,
    LayoutBrand,
    ServerArea,
    ServerLayoutModel,
} from './layouts';
export type { ServerConflict } from './server/state';
export type { AppSearchModel, AppSearchResult } from './search';
export type { OpaqueModel, OpaquePageName, PageViewName } from './pages';
