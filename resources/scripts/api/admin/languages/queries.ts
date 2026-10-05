import { useQuery } from '@tanstack/react-query';

import { adminListLanguagesOptions } from '@/api/generated/@tanstack/react-query.gen';
import type { AdminListLanguagesResponse } from '@/api/generated';

export type AdminLanguages = AdminListLanguagesResponse;

export const adminLanguagesQueryOptions = () => adminListLanguagesOptions();

type AdminLanguagesQueryOptions = { enabled?: boolean };

export const useAdminLanguages = (options?: AdminLanguagesQueryOptions) =>
    useQuery({ ...adminLanguagesQueryOptions(), ...options });
