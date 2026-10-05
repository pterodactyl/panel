import '@/api/generated/core/types.gen';

declare module '@/api/generated/core/types.gen' {
    interface ClientMeta {
        email?: string;
        name?: string;
        short?: string;
    }
}
