export type {
    AdminDatabaseHostResource,
    AdminLocationResource,
    AdminMountResource,
    AdminNodeResource,
    AdminServerResource,
    AdminEggResource,
    AdminUserResource,
    ClientGetStartupConfigurationResponse,
    ClientFileObjectResource,
    ClientGetServerResponse,
    ClientGetServerResourcesResponse,
    ClientGetServerLogsResponse,
    ClientListFilesResponse,
    ClientListServerBackupsResponse,
    ClientGetExtensionJobProgressResponse,
    ClientSendPowerActionRequest,
    ExtensionFields,
} from './generated/types.gen';

import type { ClientGetExtensionJobProgressResponse } from './generated/types.gen';
export type ExtensionJobProgress = ClientGetExtensionJobProgressResponse['data'];
