import { Model, UUID } from '@/api/admin/index';

export interface Egg extends Model {
    id: number;
    uuid: UUID;
    startup: string;
    relationships: {
        variables?: EggVariable[];
    };
}

export interface EggVariable extends Model {
    id: number;
    eggId: number;
    name: string;
    description: string;
    environmentVariable: string;
    defaultValue: string;
    isUserViewable: boolean;
    isUserEditable: boolean;
    isRequired: boolean;
    rules: string;
    createdAt: Date;
    updatedAt: Date;
}
