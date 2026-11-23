import type { AdminDatabaseHost } from '@/api/admin/database-hosts/queries';
import i18next from 'i18next';
import { type NumberInputValue, requiredNumber, submittedNumber } from '@/components/admin/numberInput';
import { requiredWithMaxLength } from '@/components/form/validators';
import { type ExtensionFormValues, initialExtensionValues } from '@/extensions/forms';

export interface DatabaseHostFormValues {
    name: string;
    host: string;
    port: NumberInputValue;
    username: string;
    password: string;
    nodeId: string;
    extensions: ExtensionFormValues;
}

type TextValidator = ({ value }: { value: string }) => string | undefined;

export const newDatabaseHostFormValues = (): DatabaseHostFormValues => ({
    name: '',
    host: '',
    port: 3306,
    username: '',
    password: '',
    nodeId: '',
    extensions: initialExtensionValues(),
});

export const databaseHostFormValues = (host: AdminDatabaseHost): DatabaseHostFormValues => ({
    name: host.attributes.name,
    host: host.attributes.host,
    port: host.attributes.port,
    username: host.attributes.username,
    password: '',
    nodeId: host.attributes.node_id === null ? '' : String(host.attributes.node_id),
    extensions: initialExtensionValues(),
});

const validateName = requiredWithMaxLength(
    191,
    () => i18next.t('A name must be provided.'),
    () => i18next.t('The name must not exceed 191 characters.')
);

const validateHost: TextValidator = ({ value }): string | undefined => {
    if (value.length < 1) {
        return i18next.t('A host must be provided.');
    }

    if (!/^[\w\-.]+$/.test(value)) {
        return i18next.t('The host must be a valid hostname or IP address.');
    }

    return undefined;
};

const validateUsername = requiredWithMaxLength(
    32,
    () => i18next.t('A username must be provided.'),
    () => i18next.t('The username must not exceed 32 characters.')
);

const validateRequiredPassword: TextValidator = ({ value }) =>
    value.length >= 1 ? undefined : i18next.t('A password must be provided.');

const validatePort = (input: { value: NumberInputValue }): string | undefined =>
    requiredNumber(i18next.t('A port must be provided.'), (value) =>
        value >= 1 && value <= 65535 ? undefined : i18next.t('The port must be between 1 and 65535.')
    )(input);

export const databaseHostValidators = {
    name: { onChange: validateName },
    host: { onChange: validateHost },
    port: { onChange: validatePort },
    username: { onChange: validateUsername },
    requiredPassword: { onChange: validateRequiredPassword },
};

export const databaseHostBodyFromFormValues = (values: DatabaseHostFormValues) => ({
    name: values.name,
    host: values.host,
    port: submittedNumber(values.port),
    username: values.username,
    password: values.password || undefined,
    node_id: values.nodeId === '' ? null : Number(values.nodeId),
    extensions: values.extensions,
});
