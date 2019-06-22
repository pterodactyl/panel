import * as React from 'react';
import { RouteComponentProps } from 'react-router';
import { connect } from 'react-redux';
import { pushFlashMessage, clearAllFlashMessages } from '@/redux/actions/flash';
import NetworkErrorMessage from '@/components/NetworkErrorMessage';

type State = Readonly<{
    isLoading: boolean;
    errorMessage?: string;
    code: string;
}>;

class LoginCheckpointContainer extends React.PureComponent<RouteComponentProps, State> {
    state: State = {
        code: '',
        isLoading: false,
    };

    render () {
        return (
            <React.Fragment>
                <h2 className={'text-center text-neutral-100 font-medium py-4'}>
                    Device Checkpoint
                </h2>
                <NetworkErrorMessage message={this.state.errorMessage}/>
                <form className={'login-box'} onSubmit={() => null}>
                    <p className={'text-sm text-neutral-700'}>
                        This account is protected with two-factor authentication. Please provide an authentication
                        code from your device in order to continue.
                    </p>
                    <div className={'flex mt-6'}>
                    </div>
                    <div className={'mt-6'}>
                        <button
                            type={'submit'}
                            className={'btn btn-primary btn-jumbo'}
                            disabled={this.state.isLoading || this.state.code.length !== 6}
                        >
                            {this.state.isLoading ?
                                <span className={'spinner white'}>&nbsp;</span>
                                :
                                'Continue'
                            }
                        </button>
                    </div>
                </form>
            </React.Fragment>
        );
    }
}

const mapDispatchToProps = {
    pushFlashMessage,
    clearAllFlashMessages,
};

export default connect(null, mapDispatchToProps)(LoginCheckpointContainer);
