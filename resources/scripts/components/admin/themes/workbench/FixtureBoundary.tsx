import { Component, type ReactNode } from 'react';

interface State {
    error: Error | null;
}

/** Shows why a fixture failed to render instead of taking the workbench down with it. */
export default class FixtureBoundary extends Component<{ children?: ReactNode }, State> {
    state: State = { error: null };

    static getDerivedStateFromError(cause: unknown): State {
        return { error: cause instanceof Error ? cause : new Error(String(cause)) };
    }

    render() {
        const { error } = this.state;

        if (!error) {
            return this.props.children;
        }

        return (
            <div role='alert' className='m-4 rounded-sm border border-destructive/40 bg-destructive/10 p-4 text-sm'>
                <p className='font-medium text-destructive'>This fixture failed to render.</p>
                <pre className='mt-2 whitespace-pre-wrap font-mono text-xs text-foreground'>{error.message}</pre>
                <button
                    type='button'
                    className='mt-3 rounded-sm border border-border bg-popover px-2 py-1 text-xs hover:bg-secondary'
                    onClick={() => this.setState({ error: null })}
                >
                    Retry
                </button>
            </div>
        );
    }
}
