import { useSearch } from '@tanstack/react-router';
import { isFiniteNumber, isString } from '@/lib/objects';

export type PageSearch = {
    page?: number;
};

export type DashboardSearch = PageSearch & {
    type?: 'admin';
};

export interface RawSearch {
    email?: string | number | boolean | null;
    event?: string | number | boolean | null;
    filter?: string | number | boolean | null;
    page?: string | number | boolean | null;
    sort?: string | number | boolean | null;
    type?: string | number | boolean | null;
    user?: string | number | boolean | null;
}

/** The URL text of a search value the router JSON-parsed. */
export const searchText = (value: RawSearch[keyof RawSearch]): string | undefined => {
    if (isString(value)) {
        return value.trim() || undefined;
    }

    return isFiniteNumber(value) || value === true || value === false ? String(value) : undefined;
};

export const parsePageSearch = (search: RawSearch): PageSearch => {
    const page = isString(search.page) || isFiniteNumber(search.page) ? Number(search.page) : Number.NaN;

    return Number.isSafeInteger(page) && page >= 1 ? { page } : {};
};

export const getPageSearch = (page: number): PageSearch => (page > 1 ? { page } : {});

export const usePageSearch = () => parsePageSearch(useSearch({ strict: false })).page ?? 1;

export const parseDashboardSearch = (search: RawSearch): DashboardSearch => {
    const result: DashboardSearch = { ...parsePageSearch(search) };

    if (search.type === 'admin') {
        result.type = 'admin';
    }

    return result;
};

export const useDashboardSearch = () => {
    const { page = 1, type } = parseDashboardSearch(useSearch({ strict: false }));

    return { page, type };
};

export type ActivitySearch = PageSearch & {
    event?: string;
    user?: string;
};

export const parseActivitySearch = (search: RawSearch): ActivitySearch => {
    const result: ActivitySearch = { ...parsePageSearch(search) };
    const event = searchText(search.event);
    const user = searchText(search.user);

    if (event) {
        result.event = event;
    }

    if (user) {
        result.user = user;
    }

    return result;
};

export const useActivitySearch = () => {
    const { page = 1, event, user } = parseActivitySearch(useSearch({ strict: false }));

    return { page, event, user };
};

export const parseEmailSearch = (search: RawSearch): { email?: string } => {
    const email = searchText(search.email);

    return email ? { email } : {};
};
