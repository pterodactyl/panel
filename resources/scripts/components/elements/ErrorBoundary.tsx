import React from 'react';
import tw from 'twin.macro';
import Icon from '@/components/elements/Icon';
import { faExclamationTriangle } from '@fortawesome/free-solid-svg-icons';
import { Trans } from 'react-i18next';

interface State {
    hasError: boolean;
}

// eslint-disable-next-line @typescript-eslint/ban-types
class ErrorBoundary extends React.Component<{}, State> {
    state: State = {
        hasError: false,
    };

    static getDerivedStateFromError () {
        return { hasError: true };
    }

    componentDidCatch (error: Error) {
        console.error(error);
    }

    render () {
        return this.state.hasError ?
            <div css={tw`flex items-center justify-center w-full my-4`}>
                <div css={tw`flex items-center bg-neutral-900 rounded p-3 text-red-500`}>
                    <Icon icon={faExclamationTriangle} css={tw`h-4 w-auto mr-2`}/>
                    <p css={tw`text-sm text-neutral-100`}>
                        <Trans i18nKey={'error_long'} ns={'elements'}/>
                    </p>
                </div>
            </div>
            :
            this.props.children;
    }
}

export default ErrorBoundary;
