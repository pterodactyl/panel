import { newExtensionFieldValues } from '@/extensions/useExtensionFormFields';
import type { AdminDatabaseHost } from '@/api/admin/database-hosts/queries';
import { requiredNumber, submittedNumber } from '@/components/admin/numberInput';
import type { LoadedExtensionFieldValues } from '@/extensions/formFields';
import type { DatabaseHostFormValues } from '@/components/admin/databases/formValues';

export type { DatabaseHostFormValues };

type TextValidator = ({ value }: { value: string }) => string | undefined;

export const newDatabaseHostFormValues = (): DatabaseHostFormValues => ({
    name: '',
    host: '',
    port: 3306,
    username: '',
    password: '',
    nodeId: '',
    extensions: newExtensionFieldValues('admin.databaseHost'),
});

export const databaseHostFormValues = (
    host: AdminDatabaseHost,
    extensions: LoadedExtensionFieldValues = {}
): DatabaseHostFormValues => ({
    name: host.attributes.name,
    host: host.attributes.host,
    port: host.attributes.port,
    username: host.attributes.username,
    password: '',
    nodeId: host.attributes.node_id === null ? '' : String(host.attributes.node_id),
    extensions,
});

const validateName: TextValidator = ({ value }) =>
    value.length < 1
        ? 'A name must be provided.'
        : value.length > 191
          ? 'The name must not exceed 191 characters.'
          : undefined;

const validateHost: TextValidator = ({ value }) =>
    value.length < 1
        ? 'A host must be provided.'
        : !/^[\w\-.]+$/.test(value)
          ? 'The host must be a valid hostname or IP address.'
          : undefined;

const validateUsername: TextValidator = ({ value }) =>
    value.length < 1
        ? 'A username must be provided.'
        : value.length > 32
          ? 'The username must not exceed 32 characters.'
          : undefined;

const validateRequiredPassword: TextValidator = ({ value }) =>
    value.length >= 1 ? undefined : 'A password must be provided.';

export const databaseHostValidators = {
    name: { onChange: validateName },
    host: { onChange: validateHost },
    port: {
        onChange: requiredNumber('A port must be provided.', (value) =>
            value >= 1 && value <= 65535 ? undefined : 'The port must be between 1 and 65535.'
        ),
    },
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
