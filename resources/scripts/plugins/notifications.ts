import { toast, type ExternalToast } from 'sonner';
import { httpErrorToHuman } from '@/api/http';

type NotificationOptions = Pick<ExternalToast, 'action' | 'description' | 'duration' | 'id'>;

const DEFAULT_DURATION = 8000;

export const notifyServerError = (title: string, options: NotificationOptions = {}) =>
    toast.error(title, {
        duration: DEFAULT_DURATION,
        ...options,
    });

export const notifyHttpError = (cause: unknown, title: string, options: NotificationOptions = {}) => {
    console.error(cause);

    return notifyServerError(title, {
        description: httpErrorToHuman(cause),
        ...options,
    });
};

export const dismissNotification = (id: string) => {
    toast.dismiss(id);
};
