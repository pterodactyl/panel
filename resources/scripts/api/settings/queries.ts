import { queryOptions, useQuery, type QueryClient } from '@tanstack/react-query';
import type { SiteSettings } from '@/api/settings/types';
import { getBootstrapSiteSettings } from '@/bootstrap';

export const siteSettingsQueryKey = ['bootstrap', 'site-settings'] as const;

const getSiteSettings = (): SiteSettings => {
    const settings = getBootstrapSiteSettings();

    if (!settings) {
        throw new Error('Site settings have not been hydrated.');
    }

    return settings;
};

/** Bootstrap data with no fetcher; changed only by `setSiteSettingsQueryData`. */
export const siteSettingsQueryOptions = () =>
    queryOptions({
        queryKey: siteSettingsQueryKey,
        initialData: getSiteSettings,
        staleTime: 'static',
    });

export const useSiteSettings = () => useQuery(siteSettingsQueryOptions()).data;

export const setSiteSettingsQueryData = (
    queryClient: QueryClient,
    updater: (settings: SiteSettings) => SiteSettings
) => {
    queryClient.setQueryData<SiteSettings>(siteSettingsQueryKey, (settings) => updater(settings ?? getSiteSettings()));
};
