export type {
    AdminNodeResource,
    AdminServerResource,
    AdminEggResource,
    AdminUserResource,
    AdminLocationResource,
    AdminMountResource,
    AdminDatabaseHostResource,
    ClientGetStartupConfigurationResponse,
    ClientFileObjectResource,
    ClientGetServerResponse,
    ClientGetServerResourcesResponse,
    ClientGetServerLogsResponse,
    ClientListFilesResponse,
    ClientListServerBackupsResponse,
    ClientGetExtensionJobProgressResponse,
    ClientSendPowerActionRequest,
} from './generated/types.gen';

import type { ClientGetExtensionJobProgressResponse } from './generated/types.gen';
export type ExtensionJobProgress = ClientGetExtensionJobProgressResponse['data'];
