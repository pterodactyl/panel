import { Model, UUID } from '@/api/admin/index';
import { Server } from '@/api/admin/server';
import http from '@/api/http';
import { AdminTransformers } from '@/api/admin/transformers';

export interface User extends Model {
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
        servers?: Server[];
    };
}

export interface UserRole extends Model {
    id: string;
    name: string;
    description: string;
}

export const getUser = async (id: string | number): Promise<User> => {
    const { data } = await http.get(`/api/application/users/${id}`);

    return AdminTransformers.toUser(data.data);
};
