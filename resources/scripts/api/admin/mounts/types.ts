import type { ExtensionFieldValues } from '@/extensions/formFields';

export interface MountValues {
    name: string;
    description: string;
    source: string;
    target: string;
    readOnly: boolean;
    userMountable: boolean;
    extensions: ExtensionFieldValues<'admin.mount'>;
}
