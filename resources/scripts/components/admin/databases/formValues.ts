import type { NumberInputValue } from '@/components/admin/numberInput';
import type { ExtensionFieldValues } from '@/extensions/formFields';

export interface DatabaseHostFormValues {
    name: string;
    host: string;
    port: NumberInputValue;
    username: string;
    password: string;
    nodeId: string;
    extensions: ExtensionFieldValues<'admin.databaseHost'>;
}
