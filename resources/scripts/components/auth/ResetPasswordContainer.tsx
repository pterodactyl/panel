import * as React from 'react';
import { RouteComponentProps } from 'react-router';
import { parse } from 'query-string';
import { Link } from 'react-router-dom';
import NetworkErrorMessage from '@/components/NetworkErrorMessage';
import performPasswordReset from '@/api/auth/performPasswordReset';
import { httpErrorToHuman } from '@/api/http';
import { connect } from 'react-redux';
import { pushFlashMessage, clearAllFlashMessages } from '@/redux/actions/flash';
import LoginFormContainer from '@/components/auth/LoginFormContainer';

type State = Readonly<{
    email?: string;
    password?: string;
    passwordConfirm?: string;
    isLoading: boolean;
    errorMessage?: string;
}>;

type Props = Readonly<RouteComponentProps<{ token: string }> & {
    pushFlashMessage: typeof pushFlashMessage;
    clearAllFlashMessages: typeof clearAllFlashMessages;
}>;

class ResetPasswordContainer extends React.PureComponent<Props, State> {
    state: State = {
        isLoading: false,
    };

    componentDidMount () {
        const parsed = parse(this.props.location.search);

        this.setState({ email: parsed.email as string || undefined });
    }

    canSubmit () {
        if (!this.state.password || !this.state.email) {
            return false;
        }

        return this.state.password.length >= 8 && this.state.password === this.state.passwordConfirm;
    }

    onPasswordChange = (e: React.ChangeEvent<HTMLInputElement>) => this.setState({
        password: e.target.value,
    });

    onPasswordConfirmChange = (e: React.ChangeEvent<HTMLInputElement>) => this.setState({
        passwordConfirm: e.target.value,
    });

    onSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        const { password, passwordConfirm, email } = this.state;
        if (!password || !email || !passwordConfirm) {
            return;
        }

        this.props.clearAllFlashMessages();
        this.setState({ isLoading: true }, () => {
            performPasswordReset(email, {
                token: this.props.match.params.token,
                password: password,
                passwordConfirmation: passwordConfirm,
            })
                .then(response => {
                    if (response.redirectTo) {
                        // @ts-ignore
                        window.location = response.redirectTo;
                        return;
                    }

                    this.props.pushFlashMessage({
                        type: 'success',
                        message: 'Your password has been reset, please login to continue.',
                    });
                    this.props.history.push('/login');
                })
                .catch(error => {
                    console.error(error);
                    this.setState({ errorMessage: httpErrorToHuman(error) });
                })
                .then(() => this.setState({ isLoading: false }));
        });
    };

    render () {
        return (
            <div>
                <h2 className={'text-center text-neutral-100 font-medium py-4'}>
                    Reset Password
                </h2>
                <NetworkErrorMessage message={this.state.errorMessage}/>
                <LoginFormContainer onSubmit={this.onSubmit}>
                    <label>Email</label>
                    <input value={this.state.email || ''} disabled={true}/>
                    <div className={'mt-6'}>
                        <label htmlFor={'new_password'}>New Password</label>
                        <input
                            id={'new_password'}
                            type={'password'}
                            required={true}
                            onChange={this.onPasswordChange}
                        />
                        <p className={'input-help'}>
                            Passwords must be at least 8 characters in length.
                        </p>
                    </div>
                    <div className={'mt-6'}>
                        <label htmlFor={'new_password_confirm'}>Confirm New Password</label>
                        <input
                            id={'new_password_confirm'}
                            type={'password'}
                            required={true}
                            onChange={this.onPasswordConfirmChange}
                        />
                    </div>
                    <div className={'mt-6'}>
                        <button
                            type={'submit'}
                            className={'btn btn-primary btn-jumbo'}
                            disabled={this.state.isLoading || !this.canSubmit()}
                        >
                            {this.state.isLoading ?
                                <span className={'spinner white'}>&nbsp;</span>
                                :
                                'Reset Password'
                            }
                        </button>
                    </div>
                    <div className={'mt-6 text-center'}>
                        <Link
                            to={'/login'}
                            className={'text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600'}
                        >
                            Return to Login
                        </Link>
                    </div>
                </LoginFormContainer>
            </div>
        );
    }
}

const mapDispatchToProps = {
    pushFlashMessage,
    clearAllFlashMessages,
};

export default connect(null, mapDispatchToProps)(ResetPasswordContainer);
