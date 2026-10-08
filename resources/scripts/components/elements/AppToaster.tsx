import { CircleAlert, CircleCheck, Info, TriangleAlert } from 'lucide-react';
import { Toaster } from 'sonner';

const iconClassName = 'h-4 w-4 shrink-0';

const AppToaster = () => (
    <Toaster
        closeButton
        theme='dark'
        position='top-right'
        visibleToasts={4}
        toastOptions={{
            unstyled: true,
            classNames: {
                toast: 'relative flex w-full items-start gap-3 rounded-md border border-border bg-popover py-3 pr-12 pl-4 text-popover-foreground shadow-lg',
                title: 'text-sm font-medium leading-tight tracking-normal text-popover-foreground',
                description: 'mt-1 text-sm leading-snug tracking-normal text-muted-foreground',
                closeButton:
                    'absolute! top-2! right-3! left-auto! flex! h-7! w-7! translate-none! items-center justify-center rounded-sm! border! border-transparent! bg-transparent! text-muted-foreground! transition-colors hover:border-border! hover:bg-secondary! hover:text-foreground! focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none [&>svg]:h-4 [&>svg]:w-4',
                actionButton:
                    'ml-auto -mt-1 inline-flex h-7 shrink-0 items-center rounded-sm bg-primary px-3 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90',
                icon: 'mt-0.5 flex shrink-0 items-center',
                error: 'border-destructive/70',
                success: 'border-success/70',
                warning: 'border-warning/70',
                info: 'border-accent/70',
            },
        }}
        icons={{
            error: <CircleAlert className={`${iconClassName} text-destructive`} />,
            success: <CircleCheck className={`${iconClassName} text-success`} />,
            warning: <TriangleAlert className={`${iconClassName} text-warning`} />,
            info: <Info className={`${iconClassName} text-accent`} />,
        }}
    />
);

export default AppToaster;
