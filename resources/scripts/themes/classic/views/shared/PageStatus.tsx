import type { ReactNode } from 'react';
import { ServerError, Spinner } from '@/sdk';
import type { PageStatusModel } from '@/sdk/theme';

interface Props {
    readonly model: PageStatusModel;
    readonly className?: string;
    /** The page, shown once its data has loaded. */
    readonly children: ReactNode;
}

/** A page's error with a retry, or a spinner while its data loads, in place of the page. */
export default function PageStatus({ model, className, children }: Props) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} className={className} />;
    }

    if (model.loading) {
        return (
            <div className={className}>
                <Spinner size='large' centered />
            </div>
        );
    }

    return children;
}
