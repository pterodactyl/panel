import type { ExtensionFormValues } from '@/extensions/forms';

export interface UserValues {
    email: string;
    username: string;
    nameFirst: string;
    nameLast: string;
    password: string;
    rootAdmin: boolean;
    language: string;
    extensions: ExtensionFormValues;
}
