import type { ExtensionFieldValues } from '@/extensions/formFields';

export interface LocationValues {
    short: string;
    long: string;
    extensions: ExtensionFieldValues<'admin.location'>;
}
