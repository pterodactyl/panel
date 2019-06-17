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

    moveToNextInput (e: React.KeyboardEvent<HTMLInputElement>, isBackspace: boolean = false) {
        const form = e.currentTarget.form;

        if (form) {
            const index = Array.prototype.indexOf.call(form, e.currentTarget);
            const element = form.elements[index + (isBackspace ? -1 : 1)];

            // @ts-ignore
            element && element.focus();
        }
    }

    handleNumberInput = (e: React.KeyboardEvent<HTMLInputElement>) => {
        const number = Number(e.key);
        if (isNaN(number)) {
            return;
        }

        this.setState(s => ({ code: s.code + number.toString() }));
        this.moveToNextInput(e);
    };

    handleBackspace = (e: React.KeyboardEvent<HTMLInputElement>) => {
        const isBackspace = e.key === 'Delete' || e.key === 'Backspace';

        if (!isBackspace || e.currentTarget.value.length > 0) {
            e.currentTarget.value = '';
            return;
        }

        this.setState(s => ({ code: s.code.substring(0, s.code.length - 2) }));
        e.currentTarget.value = '';
        this.moveToNextInput(e, true);
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
                        {
                            [1, 2, 3, 4, 5, 6].map((_, index) => (
                                <input
                                    autoFocus={index === 0}
                                    key={`input_${index}`}
                                    type={'number'}
                                    onKeyPress={this.handleNumberInput}
                                    onKeyDown={this.handleBackspace}
                                    maxLength={1}
                                    className={`input block flex-1 text-center text-lg ${index === 5 ? undefined : 'mr-6'}`}
                                />
                            ))
                        }
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
