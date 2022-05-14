import { Model } from '@/api/definitions';

interface SSHKey extends Model {
    name: string;
    publicKey: string;
    fingerprint: string;
    createdAt: Date;
}
