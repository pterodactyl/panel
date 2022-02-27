import { Model as BaseModel, UUID } from '@/api/definitions';
import { Server } from '@/api/admin/server';
import { MarkRequired } from 'ts-essentials';

interface Model extends BaseModel {
    relationships: Record<string, unknown>;
}

/**
 * Allows a model to have optional relationships that are marked as being
 * present in a given pathway. This allows different API calls to specify the
 * "completeness" of a response object without having to make every API return
 * the same information, or every piece of logic do explicit null checking.
 *
 * Example:
 *  >> const user: WithLoadedRelations<User, 'servers'> = {};
 *  >> // "user.servers" is no longer potentially undefined.
 */
type WithLoadedRelations<M extends Model, R extends keyof M['relationships']> = M & {
    relationships: MarkRequired<M['relationships'], R>;
};

interface User extends Model {
    id: number;
    uuid: UUID;
    externalId: string;
    username: string;
    email: string;
    language: string;
    adminRoleId: number | null;
    roleName: string;
    isRootAdmin: boolean;
    isUsingTwoFactor: boolean;
    avatarUrl: string;
    createdAt: Date;
    updatedAt: Date;
    relationships: {
        role: UserRole | null;
        // TODO: just use an API call, this is probably a bad idea for performance.
        servers?: Server[];
    };
}

interface UserRole extends Model {
    id: string;
    name: string;
    description: string;
}
