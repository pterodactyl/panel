export interface UserData {
    uuid: string;
    username: string;
    email: string;
    language: string;
    rootAdmin: boolean;
    useTotp: boolean;
    createdAt: Date;
    updatedAt: Date;
    // Only set on users loaded through the Application API.
    id?: number;
    firstName?: string;
    lastName?: string;
    externalId?: string | null;
}
