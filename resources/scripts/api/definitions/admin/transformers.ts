/* eslint-disable camelcase */
import { Allocation, Node } from '@/api/admin/node';
import { Server, ServerVariable } from '@/api/admin/server';
import { FractalResponseData, FractalResponseList } from '@/api/http';
import * as Models from '@definitions/admin/models';
import { Location } from '@/api/admin/location';
import { Egg, EggVariable } from '@/api/admin/egg';
import { Nest } from '@/api/admin/nest';

const isList = (data: FractalResponseList | FractalResponseData): data is FractalResponseList => data.object === 'list';

function transform<T, M = undefined>(
    data: undefined,
    transformer: (callback: FractalResponseData) => T,
    missing?: M,
): undefined;
function transform<T, M>(
    data: FractalResponseData | undefined,
    transformer: (callback: FractalResponseData) => T,
    missing?: M,
): T | M | undefined;
function transform<T, M>(
    data: FractalResponseList | undefined,
    transformer: (callback: FractalResponseData) => T,
    missing?: M,
): T[] | undefined;
function transform<T>(
    data: FractalResponseData | FractalResponseList | undefined,
    transformer: (callback: FractalResponseData) => T,
    missing = undefined,
) {
    if (data === undefined) return undefined;

    if (isList(data)) {
        return data.data.map(transformer);
    }

    return !data ? missing : transformer(data);
}

export default class Transformers {
    static toServer = ({ attributes }: FractalResponseData): Server => {
        const { oom_killer, ...limits } = attributes.limits;
        const { allocations, egg, nest, node, user, variables } = attributes.relationships || {};

        return {
            id: attributes.id,
            uuid: attributes.uuid,
            externalId: attributes.external_id,
            identifier: attributes.identifier,
            name: attributes.name,
            description: attributes.description,
            status: attributes.status,
            ownerId: attributes.owner_id,
            nodeId: attributes.node_id,
            allocationId: attributes.allocation_id,
            eggId: attributes.egg_id,
            nestId: attributes.nest_id,
            limits: { ...limits, oomKiller: oom_killer },
            featureLimits: attributes.feature_limits,
            container: attributes.container,
            createdAt: new Date(attributes.created_at),
            updatedAt: new Date(attributes.updated_at),
            relationships: {
                allocations: transform(allocations as FractalResponseList | undefined, this.toAllocation),
                nest: transform(nest as FractalResponseData | undefined, this.toNest),
                egg: transform(egg as FractalResponseData | undefined, this.toEgg),
                node: transform(node as FractalResponseData | undefined, this.toNode),
                user: transform(user as FractalResponseData | undefined, this.toUser),
                variables: transform(variables as FractalResponseList | undefined, this.toServerEggVariable),
            },
        };
    };

    static toNode = ({ attributes }: FractalResponseData): Node => {
        return {
            id: attributes.id,
            uuid: attributes.uuid,
            isPublic: attributes.public,
            locationId: attributes.location_id,
            databaseHostId: attributes.database_host_id,
            name: attributes.name,
            description: attributes.description,
            fqdn: attributes.fqdn,
            ports: {
                http: {
                    public: attributes.publicPortHttp,
                    listen: attributes.listenPortHttp,
                },
                sftp: {
                    public: attributes.publicPortSftp,
                    listen: attributes.listenPortSftp,
                },
            },
            scheme: attributes.scheme,
            isBehindProxy: attributes.behindProxy,
            isMaintenanceMode: attributes.maintenance_mode,
            memory: attributes.memory,
            memoryOverallocate: attributes.memory_overallocate,
            disk: attributes.disk,
            diskOverallocate: attributes.disk_overallocate,
            uploadSize: attributes.upload_size,
            daemonBase: attributes.daemonBase,
            createdAt: new Date(attributes.created_at),
            updatedAt: new Date(attributes.updated_at),
            relationships: {
                location: transform(attributes.relationships?.location as FractalResponseData, this.toLocation),
            },
        };
    };

    static toUserRole = ({ attributes }: FractalResponseData): Models.UserRole => ({
        id: attributes.id,
        name: attributes.name,
        description: attributes.description,
        relationships: {},
    });

    static toUser = ({ attributes }: FractalResponseData): Models.User => {
        return {
            id: attributes.id,
            uuid: attributes.uuid,
            externalId: attributes.external_id,
            username: attributes.username,
            email: attributes.email,
            language: attributes.language,
            adminRoleId: attributes.adminRoleId || null,
            roleName: attributes.role_name,
            isRootAdmin: attributes.root_admin,
            isUsingTwoFactor: attributes['2fa'] || false,
            avatarUrl: attributes.avatar_url,
            createdAt: new Date(attributes.created_at),
            updatedAt: new Date(attributes.updated_at),
            relationships: {
                role: transform(attributes.relationships?.role as FractalResponseData, this.toUserRole) || null,
            },
        };
    };

    static toLocation = ({ attributes }: FractalResponseData): Location => ({
        id: attributes.id,
        short: attributes.short,
        long: attributes.long,
        createdAt: new Date(attributes.created_at),
        updatedAt: new Date(attributes.updated_at),
        relationships: {
            nodes: transform(attributes.relationships?.node as FractalResponseList, this.toNode),
        },
    });

    static toEgg = ({ attributes }: FractalResponseData): Egg => ({
        id: attributes.id,
        uuid: attributes.uuid,
        nestId: attributes.nest_id,
        author: attributes.author,
        name: attributes.name,
        description: attributes.description,
        features: attributes.features,
        dockerImages: attributes.docker_images,
        configFiles: attributes.config?.files,
        configStartup: attributes.config?.startup,
        configStop: attributes.config?.stop,
        configFrom: attributes.config?.extends,
        startup: attributes.startup,
        copyScriptFrom: attributes.copy_script_from,
        scriptContainer: attributes.script?.container,
        scriptEntry: attributes.script?.entry,
        scriptIsPrivileged: attributes.script?.privileged,
        scriptInstall: attributes.script?.install,
        createdAt: new Date(attributes.created_at),
        updatedAt: new Date(attributes.updated_at),
        relationships: {
            nest: transform(attributes.relationships?.nest as FractalResponseData, this.toNest),
            variables: transform(attributes.relationships?.variables as FractalResponseList, this.toEggVariable),
        },
    });

    static toEggVariable = ({ attributes }: FractalResponseData): EggVariable => ({
        id: attributes.id,
        eggId: attributes.egg_id,
        name: attributes.name,
        description: attributes.description,
        environmentVariable: attributes.env_variable,
        defaultValue: attributes.default_value,
        isUserViewable: attributes.user_viewable,
        isUserEditable: attributes.user_editable,
        // isRequired: attributes.required,
        rules: attributes.rules,
        createdAt: new Date(attributes.created_at),
        updatedAt: new Date(attributes.updated_at),
        relationships: {},
    });

    static toServerEggVariable = (data: FractalResponseData): ServerVariable => ({
        ...this.toEggVariable(data),
        serverValue: data.attributes.server_value,
    });

    static toAllocation = ({ attributes }: FractalResponseData): Allocation => ({
        id: attributes.id,
        ip: attributes.ip,
        port: attributes.port,
        alias: attributes.alias || null,
        isAssigned: attributes.assigned,
        relationships: {
            node: transform(attributes.relationships?.node as FractalResponseData, this.toNode),
            server: transform(attributes.relationships?.server as FractalResponseData, this.toServer),
        },
        getDisplayText(): string {
            const raw = `${this.ip}:${this.port}`;

            return !this.alias ? raw : `${this.alias} (${raw})`;
        },
    });

    static toNest = ({ attributes }: FractalResponseData): Nest => ({
        id: attributes.id,
        uuid: attributes.uuid,
        author: attributes.author,
        name: attributes.name,
        description: attributes.description,
        createdAt: new Date(attributes.created_at),
        updatedAt: new Date(attributes.updated_at),
        relationships: {
            eggs: transform(attributes.relationships?.eggs as FractalResponseList, this.toEgg),
        },
    });
}
