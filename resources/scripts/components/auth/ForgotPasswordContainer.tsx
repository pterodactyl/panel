import * as React from 'react';
import OpenInputField from '@/components/forms/OpenInputField';
import { Link } from 'react-router-dom';
import requestPasswordResetEmail from '@/api/auth/requestPasswordResetEmail';
import { connect } from 'react-redux';
import { ReduxState } from '@/redux/types';
import { pushFlashMessage, clearAllFlashMessages } from '@/redux/actions/flash';
import { httpErrorToHuman } from '@/api/http';

type Props = Readonly<{
    pushFlashMessage: typeof pushFlashMessage;
    clearAllFlashMessages: typeof clearAllFlashMessages;
}>;

type State = Readonly<{
    email: string;
    isSubmitting: boolean;
}>;

class ForgotPasswordContainer extends React.PureComponent<Props, State> {
    emailField = React.createRef<HTMLInputElement>();

    state: State = {
        email: '',
        isSubmitting: false,
    };

    handleFieldUpdate = (e: React.ChangeEvent<HTMLInputElement>) => this.setState({
        email: e.target.value,
    });

    handleSubmission = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        this.setState({ isSubmitting: true }, () => {
            this.props.clearAllFlashMessages();
            requestPasswordResetEmail(this.state.email)
                .then(response => {
                    if (this.emailField.current) {
                        this.emailField.current.value = '';
                    }

                    this.props.pushFlashMessage({
                        type: 'success', title: 'Success', message: response,
                    });
                })
                .catch(error => {
                    console.error(error);
                    this.props.pushFlashMessage({
                        type: 'error',
                        title: 'Error',
                        message: httpErrorToHuman(error),
                    });
                })
                .then(() => this.setState({ isSubmitting: false }));
        });
    };

    render () {
        return (
            <div>
                <h2 className={'text-center text-neutral-100 font-medium py-4'}>
                    Request Password Reset
                </h2>
                <form className={'login-box'} onSubmit={this.handleSubmission}>
                    <div className={'mt-3'}>
                        <OpenInputField
                            ref={this.emailField}
                            id={'email'}
                            type={'email'}
                            label={'Email'}
                            description={'Enter your account email address to receive instructions on resetting your password.'}
                            autoFocus={true}
                            required={true}
                            onChange={this.handleFieldUpdate}
                        />
                    </div>
                    <div className={'mt-6'}>
                        <button
                            className={'btn btn-primary btn-jumbo flex justify-center'}
                            disabled={this.state.isSubmitting || this.state.email.length < 5}
                        >
                            {this.state.isSubmitting ?
                                <div className={'spinner-circle spinner-sm spinner-white'}></div>
                                :
                                'Send Email'
                            }
                        </button>
                    </div>
                    <div className={'mt-6 text-center'}>
                        <Link
                            to={'/login'}
                            className={'text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700'}
                        >
                            Return to Login
                        </Link>
                    </div>
                </form>
            </div>
        );
    }
}

const mapDispatchToProps = {
    pushFlashMessage,
    clearAllFlashMessages,
};

export default connect(null, mapDispatchToProps)(ForgotPasswordContainer);
