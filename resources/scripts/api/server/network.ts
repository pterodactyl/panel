import http from '@/api/http';
import { Transformers, Allocation } from '@definitions/user';

const createServerAllocation = async (uuid: string): Promise<Allocation> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/network/allocations`);

    return Transformers.toServerAllocation(data);
};

const deleteServerAllocation = async (uuid: string, id: number): Promise<Allocation> =>
    await http.delete(`/api/client/servers/${uuid}/network/allocations/${id}`);

const setPrimaryServerAllocation = async (uuid: string, id: number): Promise<Allocation> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/network/allocations/${id}/primary`);

    return Transformers.toServerAllocation(data);
};

const setServerAllocationNotes = async (uuid: string, id: number, notes: string | null): Promise<Allocation> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/network/allocations/${id}`, { notes });

    return Transformers.toServerAllocation(data);
};

export {
    createServerAllocation,
    deleteServerAllocation,
    setPrimaryServerAllocation,
    setServerAllocationNotes,
};
