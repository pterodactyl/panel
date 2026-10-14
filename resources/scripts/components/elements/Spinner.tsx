import React, { Suspense } from 'react';
import { useLocation, useRouter } from '@tanstack/react-router';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import { replaceable, useSkin } from '@/themes/skins';

export type SpinnerSize = 'small' | 'base' | 'large';

export interface SpinnerProps {
    size?: SpinnerSize;
    centered?: boolean;
    isBlue?: boolean;
    children?: React.ReactNode;
}

type SpinnerComponentType = ((props: SpinnerProps) => React.JSX.Element) & {
    displayName?: string;
    Size: Record<'SMALL' | 'BASE' | 'LARGE', SpinnerSize>;
    Suspense: ((props: SpinnerProps) => React.JSX.Element) & { displayName?: string };
};

function CoreSpinner({ size = 'base', centered = false, isBlue = false }: SpinnerProps) {
    const ui = useSkin('Spinner', { size, blue: isBlue, centered });
    const spinner = <span {...ui('root')} />;

    return centered ? <div {...ui('container')}>{spinner}</div> : spinner;
}

const Spinner = replaceable('Spinner', CoreSpinner) as SpinnerComponentType;

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
