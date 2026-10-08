import type { AdminEgg, EggConfigurationBody, EggVariableBody } from '@/api/admin/eggs/queries';
import { isObject } from '@/lib/objects';

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
}

export interface EggVariableValues {
    name: string;
    description: string;
    envVariable: string;
    defaultValue: string;
    userViewable: boolean;
    userEditable: boolean;
    rules: string;
}

const featuresToString = (features: string[] | null): string => (features ?? []).join(', ');

const stringToFeatures = (value: string): string[] =>
    value
        .split(',')
        .map((feature) => feature.trim())
        .filter((feature) => feature.length > 0);

const nullIfEmpty = (value: string): string | null => (value.trim().length > 0 ? value : null);

const eggVariableOptions = (values: EggVariableValues): string[] => {
    const options: string[] = [];

    if (values.userViewable) {
        options.push('user_viewable');
    }

    if (values.userEditable) {
        options.push('user_editable');
    }

    return options;
};

const dockerImagesToString = (images: Record<string, string>): string =>
    Object.entries(images)
        .map(([name, image]) => (name === image ? image : `${name}|${image}`))
        .join('\n');

const prettyJson = <T>(value: T): string => {
    if (value === null || value === undefined) {
        return '';
    }

    if (isObject(value) && Object.keys(value).length === 0) {
        return '';
    }

    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return '';
    }
};

export const eggToFormValues = (egg: AdminEgg): EggFormValues => ({
    name: egg.attributes.name,
    description: egg.attributes.description ?? '',
    dockerImages: dockerImagesToString(egg.attributes.docker_images),
    startup: egg.attributes.startup,
    featuresText: featuresToString(egg.attributes.features),
    forceOutgoingIp: egg.attributes.force_outgoing_ip ?? false,
    configFrom: egg.attributes.config.extends ?? 0,
    configStop: egg.attributes.config.stop ?? '',
    configStartup: prettyJson(egg.attributes.config.startup),
    configLogs: prettyJson(egg.attributes.config.logs),
    configFiles: prettyJson(egg.attributes.config.files),
});

export const emptyEggFormValues = (): EggFormValues => ({
    name: '',
    description: '',
    dockerImages: '',
    startup: '',
    featuresText: '',
    forceOutgoingIp: false,
    configFrom: 0,
    configStop: '',
    configStartup: '',
    configLogs: '',
    configFiles: '',
});

export const toApiValues = (
    values: EggFormValues,
    json: { configLogs: string; configFiles: string; configStartup: string }
): EggConfigurationBody => {
    const usingParent = Number(values.configFrom) > 0;

    return {
        name: values.name,
        description: values.description,
        docker_images: values.dockerImages,
        startup: values.startup,
        features: stringToFeatures(values.featuresText),
        force_outgoing_ip: values.forceOutgoingIp,
        // Not edited here, but the API requires an array.
        file_denylist: [],
        config_from: usingParent ? Number(values.configFrom) : null,
        config_stop: values.configStop,
        config_startup: nullIfEmpty(json.configStartup),
        config_logs: nullIfEmpty(json.configLogs),
        config_files: nullIfEmpty(json.configFiles),
    };
};

export const eggVariableBodyFromFormValues = (values: EggVariableValues): EggVariableBody => ({
    name: values.name,
    description: values.description,
    env_variable: values.envVariable,
    default_value: values.defaultValue,
    options: eggVariableOptions(values),
    rules: values.rules,
});
