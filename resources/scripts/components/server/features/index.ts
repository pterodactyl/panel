import { ComponentType, lazy } from 'react';

/**
 * Custom features should be registered here as lazy components so that they do
 * not impact the generated JS bundle size. They will be automatically loaded in
 * whenever they are actually loaded for the client (which may be never, depending
 * on the feature and the egg).
 */
const features: Record<string, ComponentType> = {
    eula: lazy(() => import('@feature/eula/EulaModalFeature')),
    java_version: lazy(() => import('@feature/JavaVersionModalFeature')),
    gsl_token: lazy(() => import('@feature/GSLTokenModalFeature')),
    pid_limit: lazy(() => import('@feature/PIDLimitModalFeature')),
    steam_disk_space: lazy(() => import('@feature/SteamDiskSpaceFeature')),
    hytale_oauth: lazy(() => import('@feature/HytaleOauthRequireFeature')),
};

export default features;
