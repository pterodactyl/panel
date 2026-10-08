import React, { Suspense } from 'react';
import { cva } from 'class-variance-authority';
import { useLocation, useRouter } from '@tanstack/react-router';
import { cn } from '@/lib/cn';
import ErrorBoundary from '@/components/elements/ErrorBoundary';

export type SpinnerSize = 'small' | 'base' | 'large';

interface Props {
    size?: SpinnerSize;
    centered?: boolean;
    isBlue?: boolean;
    children?: React.ReactNode;
}

type SpinnerComponentType = ((props: Props) => React.JSX.Element) & {
    displayName?: string;
    Size: Record<'SMALL' | 'BASE' | 'LARGE', SpinnerSize>;
    Suspense: ((props: Props) => React.JSX.Element) & { displayName?: string };
};

const spinnerSize = cva('', {
    variants: {
        size: {
            small: 'w-4 h-4 border-2',
            base: 'w-8 h-8 border-[3px]',
            large: 'w-16 h-16 border-[6px]',
        },
    },
    defaultVariants: {
        size: 'base',
    },
});

const SpinnerComponent = ({ size, isBlue }: Props) => (
    <span
        className={cn(
            'block rounded-full animate-[spin_1s_cubic-bezier(0.55,0.25,0.25,0.7)_infinite] border-muted border-t-foreground',
            isBlue && 'border-ring border-t-primary',
            spinnerSize({ size: size ?? 'base' })
        )}
    />
);

const Spinner = (({ centered, ...props }: Props) =>
    centered ? (
        <div className={cn('flex justify-center items-center', props.size === 'large' ? 'm-20' : 'm-6')}>
            <SpinnerComponent {...props} />
        </div>
    ) : (
        <SpinnerComponent {...props} />
    )) as SpinnerComponentType;

Spinner.displayName = 'Spinner';

Spinner.Size = {
    SMALL: 'small',
    BASE: 'base',
    LARGE: 'large',
};

const LocationErrorBoundary = ({ children }: { children?: React.ReactNode }) => {
    const href = useLocation({ select: (location) => location.href });

    return <ErrorBoundary resetKeys={[href]}>{children}</ErrorBoundary>;
};

const SuspenseErrorBoundary = ({ children }: { children?: React.ReactNode }) =>
    useRouter({ warn: false }) ? (
        <LocationErrorBoundary>{children}</LocationErrorBoundary>
    ) : (
        <ErrorBoundary>{children}</ErrorBoundary>
    );

Spinner.Suspense = ({ children, centered = true, size = Spinner.Size.LARGE, ...props }) => (
    <Suspense fallback={<Spinner centered={centered} size={size} {...props} />}>
        <SuspenseErrorBoundary>{children}</SuspenseErrorBoundary>
    </Suspense>
);
Spinner.Suspense.displayName = 'Spinner.Suspense';

export default Spinner;
