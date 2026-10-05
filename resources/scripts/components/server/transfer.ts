/** `completed` comes from the source node and `success` from the destination node. */
export type TransferStatus = 'pending' | 'processing' | 'cancelling' | 'failed' | 'completed' | 'success';

// Wings publishes `failure`; `failed` and `cancelled` are its internal state names.
const transferStatuses = new Map<string, TransferStatus>([
    ['pending', 'pending'],
    ['processing', 'processing'],
    ['cancelling', 'cancelling'],
    ['failure', 'failed'],
    ['failed', 'failed'],
    ['cancelled', 'failed'],
    ['completed', 'completed'],
    ['success', 'success'],
]);

export const normalizeTransferStatus = (value: string): TransferStatus | null =>
    transferStatuses.get(value.trim().toLowerCase()) ?? null;

export const isTransferInProgress = (status: TransferStatus): boolean =>
    status === 'pending' || status === 'processing' || status === 'cancelling';

export const isTransferSuccessful = (status: TransferStatus): boolean => status === 'completed' || status === 'success';
