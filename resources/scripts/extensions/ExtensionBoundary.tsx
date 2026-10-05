import React from 'react';
import { clearExtensionError, reportExtensionError } from '@/extensions/registry';

interface Props {
    extensionId: string;
    context: string;
    resetKey: string;
    onReset: () => void;
    fallback: (error: Error, retry: () => void) => React.ReactNode;
    children?: React.ReactNode;
}

interface BoundaryState {
    error: Error | null;
}

export default class ExtensionBoundary extends React.Component<Props, BoundaryState> {
    state: BoundaryState = { error: null };

    static getDerivedStateFromError(cause: unknown) {
        return { error: cause instanceof Error ? cause : new Error(String(cause)) };
    }

    componentDidCatch(error: Error) {
        reportExtensionError(this.props.extensionId, this.props.context, error);
    }

    componentDidUpdate(previous: Props) {
        if (previous.resetKey !== this.props.resetKey && this.state.error) this.retry();
    }

    retry = () => {
        this.props.onReset();
        clearExtensionError(this.props.extensionId, this.props.context);
        this.setState({ error: null });
    };

    render() {
        return this.state.error ? this.props.fallback(this.state.error, this.retry) : this.props.children;
    }
}
