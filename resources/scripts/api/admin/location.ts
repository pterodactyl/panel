import { Model } from '@/api/admin/index';
import { Node } from '@/api/admin/node';

export interface Location extends Model {
    id: number;
    short: string;
    long: string;
    createdAt: Date;
    updatedAt: Date;
    relationships: {
        nodes?: Node[];
    };
}
