export const EXTENSION_FORM_NAMES = [
    'admin.user',
    'admin.node',
    'admin.server',
    'admin.egg',
    'admin.location',
    'admin.mount',
    'admin.databaseHost',
] as const;

export type ExtensionFormName = (typeof EXTENSION_FORM_NAMES)[number];

export interface ExtensionFormFieldMap {}

export type ExtensionFieldValues<TForm extends ExtensionFormName> = TForm extends keyof ExtensionFormFieldMap
    ? Partial<ExtensionFormFieldMap[TForm]>
    : Record<never, never>;

export type ExtensionFieldValue = string | number | boolean | Array<string | number> | null;

export type LoadedExtensionFieldValues = Record<string, Record<string, ExtensionFieldValue>>;
