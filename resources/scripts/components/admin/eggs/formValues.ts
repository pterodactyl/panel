import type { ExtensionFieldValues } from '@/extensions/formFields';

export interface EggFormValues {
    name: string;
    description: string;
    dockerImages: string;
    startup: string;
    featuresText: string;
    forceOutgoingIp: boolean;
    configFrom: number;
    configStop: string;
    configStartup: string;
    configLogs: string;
    configFiles: string;
    extensions: ExtensionFieldValues<'admin.egg'>;
}
