import type { ComponentType } from 'react';
import { lazy } from 'react';

const features = {
    eula: lazy(() => import('@feature/eula/EulaModalFeature')),
    java_version: lazy(() => import('@feature/JavaVersionModalFeature')),
    gsl_token: lazy(() => import('@feature/GSLTokenModalFeature')),
    pid_limit: lazy(() => import('@feature/PIDLimitModalFeature')),
    steam_disk_space: lazy(() => import('@feature/SteamDiskSpaceFeature')),
    hytale_oauth: lazy(() => import('@feature/HytaleOauthRequireFeature')),
} satisfies Record<string, ComponentType>;

export default features;
