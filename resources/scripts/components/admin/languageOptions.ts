import type { AdminLanguages } from '@/api/admin/languages/queries';
import type { SelectOption } from '@/components/ui/Select';
import { getBootstrapSiteSettings } from '@/bootstrap';

export const PANEL_DEFAULT_LANGUAGE = '';

export const languageOptions = (
    languages: AdminLanguages | undefined,
    current?: string | null,
    withPanelDefault = false
): SelectOption[] => {
    const options = Object.entries(languages ?? {}).map(([value, label]) => ({ value, label }));
    const panelDefault = getBootstrapSiteSettings()?.locale ?? 'en';
    const defaults = withPanelDefault
        ? [{ value: PANEL_DEFAULT_LANGUAGE, label: `Panel default (${languages?.[panelDefault] ?? panelDefault})` }]
        : [];

    if (current && !options.some((option) => option.value === current)) {
        return [...defaults, { value: current, label: current }, ...options];
    }

    return [...defaults, ...options];
};
