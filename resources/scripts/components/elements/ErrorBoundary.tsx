import React from 'react';
import Icon from '@/components/elements/Icon';
import { TriangleAlert } from 'lucide-react';

type ResetKey = string | number | boolean | null | undefined;

const retryButtonClass = [
    'ml-3 shrink-0 rounded-sm border border-border bg-popover px-2 py-1 text-xs uppercase tracking-wide',
    'text-foreground transition-colors duration-150 hover:bg-secondary',
].join(' ');

interface Props {
    children?: React.ReactNode;
    resetKeys?: readonly ResetKey[];
}

interface State {
    hasError: boolean;
}

const keysChanged = (previous: readonly ResetKey[] = [], next: readonly ResetKey[] = []): boolean =>
    previous.length !== next.length || previous.some((key, index) => !Object.is(key, next[index]));

class ErrorBoundary extends React.Component<Props, State> {
    state: State = {
        hasError: false,
    };

    static getDerivedStateFromError() {
        return { hasError: true };
    }

    componentDidCatch(error: Error) {
        console.error(error);
    }

    componentDidUpdate(prevProps: Props, prevState: State) {
        if (prevState.hasError && this.state.hasError && keysChanged(prevProps.resetKeys, this.props.resetKeys)) {
            this.reset();
        }
    }

    reset = () => {
        this.setState({ hasError: false });
    };

    render() {
        return this.state.hasError ? (
            <div className={'flex items-center justify-center w-full my-4'}>
                <div role={'alert'} className={'flex items-center bg-muted rounded-sm p-3 text-destructive'}>
                    <Icon icon={TriangleAlert} className={'h-4 w-auto mr-2'} />
                    <p className={'text-sm text-foreground'}>
                        An error was encountered by the application while rendering this view. Try refreshing the page.
                    </p>
                    <button type={'button'} className={retryButtonClass} onClick={this.reset}>
                        Retry
                    </button>
                </div>
            </div>
        ) : (
            this.props.children
        );
    }
}

export default ErrorBoundary;
