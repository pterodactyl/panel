import * as Models from './models';
import { FractalResponseData, FractalResponseList } from '@/api/http';

export default class Transformers {
    static toSecurityKey = ({ attributes }: FractalResponseData): Models.SecurityKey => {
        return {
            uuid: attributes.uuid,
            name: attributes.name,
            type: attributes.type,
            publicKeyId: attributes.public_key_id,
            createdAt: new Date(attributes.created_at),
            updatedAt: new Date(attributes.updated_at),
        };
    }

    static toPersonalAccessToken = ({ attributes }: FractalResponseData): Models.PersonalAccessToken => {
        return {
            identifier: attributes.token_id,
            description: attributes.description,
            createdAt: new Date(attributes.created_at),
            updatedAt: new Date(attributes.updated_at),
            lastUsedAt: attributes.last_used_at ? new Date(attributes.last_used_at) : null,
        };
    }

    static toServerAllocation = ({ attributes }: FractalResponseData): Models.Allocation => {
        return {
            id: attributes.id,
            ip: attributes.ip,
            alias: attributes.ip_alias,
            port: attributes.port,
            notes: attributes.notes,
            isDefault: attributes.is_default,
        };
    }

    static toServerEggVariable = ({ attributes }: FractalResponseData): Models.ServerEggVariable => {
        return {
            name: attributes.name,
            description: attributes.description,
            envVariable: attributes.env_variable,
            defaultValue: attributes.default_value,
            serverValue: attributes.server_value,
            isEditable: attributes.is_editable,
            rules: attributes.rules.split('|'),
        };
    }

    static toServer = ({ attributes }: FractalResponseData): Models.Server => {
        return {
            id: attributes.identifier,
            internalId: attributes.internal_id,
            uuid: attributes.uuid,
            name: attributes.name,
            node: attributes.node,
            status: attributes.status,
            invocation: attributes.invocation,
            dockerImage: attributes.docker_image,
            sftpDetails: {
                ip: attributes.sftp_details.ip,
                port: attributes.sftp_details.port,
            },
            description: attributes.description ? ((attributes.description.length > 0) ? attributes.description : null) : null,
            limits: { ...attributes.limits },
            eggFeatures: attributes.egg_features || [],
            featureLimits: { ...attributes.feature_limits },
            isInstalling: attributes.status === 'installing' || attributes.status === 'install_failed',
            isTransferring: attributes.is_transferring,
            variables: ((attributes.relationships?.variables as FractalResponseList | undefined)?.data || []).map(this.toServerEggVariable),
            allocations: ((attributes.relationships?.allocations as FractalResponseList | undefined)?.data || []).map(this.toServerAllocation),
        };
    }

    static toServerBackup = ({ attributes }: FractalResponseData): Models.ServerBackup => {
        return {
            uuid: attributes.uuid,
            isSuccessful: attributes.is_successful,
            isLocked: attributes.is_locked,
            name: attributes.name,
            ignoredFiles: attributes.ignored_files,
            checksum: attributes.checksum,
            bytes: attributes.bytes,
            createdAt: new Date(attributes.created_at),
            completedAt: attributes.completed_at ? new Date(attributes.completed_at) : null,
        };
    }

    static toServerDatabase = ({ attributes }: FractalResponseData): Models.ServerDatabase => {
        return {
            id: attributes.id,
            name: attributes.name,
            username: attributes.username,
            connectionString: `${attributes.host.address}:${attributes.host.port}`,
            allowConnectionsFrom: attributes.connections_from,
            // @ts-expect-error
            password: attributes.relationships && attributes.relationships.password ? attributes.relationships.password.attributes.password : undefined,
        };
    }

    static toSubuser = ({ attributes }: FractalResponseData): Models.Subuser => {
        return {
            uuid: attributes.uuid,
            username: attributes.username,
            email: attributes.email,
            image: attributes.image,
            twoFactorEnabled: attributes['2fa_enabled'],
            createdAt: new Date(attributes.created_at),
            permissions: attributes.permissions || [],
            can: permission => (attributes.permissions || []).indexOf(permission) >= 0,
        };
    }

    static toServerTask = ({ attributes }: FractalResponseData): Models.Task => {
        return {
            id: attributes.id,
            sequenceId: attributes.sequence_id,
            action: attributes.action,
            payload: attributes.payload,
            timeOffset: attributes.time_offset,
            isQueued: attributes.is_queued,
            continueOnFailure: attributes.continue_on_failure,
            createdAt: new Date(attributes.created_at),
            updatedAt: new Date(attributes.updated_at),
        };
    }

    static toServerSchedule = ({ attributes }: FractalResponseData): Models.Schedule => {
        return {
            id: attributes.id,
            name: attributes.name,
            cron: {
                dayOfWeek: attributes.cron.day_of_week,
                month: attributes.cron.month,
                dayOfMonth: attributes.cron.day_of_month,
                hour: attributes.cron.hour,
                minute: attributes.cron.minute,
            },
            isActive: attributes.is_active,
            isProcessing: attributes.is_processing,
            onlyWhenOnline: attributes.only_when_online,
            lastRunAt: attributes.last_run_at ? new Date(attributes.last_run_at) : null,
            nextRunAt: attributes.next_run_at ? new Date(attributes.next_run_at) : null,
            createdAt: new Date(attributes.created_at),
            updatedAt: new Date(attributes.updated_at),
            // @ts-expect-error
            tasks: (attributes.relationships?.tasks?.data || []).map((row: any) => this.toServerTask(row.attributes)),
        };
    }
}
