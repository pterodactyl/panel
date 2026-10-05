import type { AdminLanguages } from '@/api/admin/languages/queries';
import type { SelectOption } from '@/components/ui/Select';

export const languageOptions = (languages: AdminLanguages | undefined, current?: string): SelectOption[] => {
    const options = Object.entries(languages ?? {}).map(([value, label]) => ({ value, label }));

    if (current && !options.some((option) => option.value === current)) {
        return [{ value: current, label: current }, ...options];
    }

    return options;
};
