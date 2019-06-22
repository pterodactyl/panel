import * as React from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import login from '@/api/auth/login';
import { httpErrorToHuman } from '@/api/http';
import NetworkErrorMessage from '@/components/NetworkErrorMessage';

type State = Readonly<{
    errorMessage?: string;
    isLoading: boolean;
    username?: string;
    password?: string;
}>;

export default class LoginContainer extends React.PureComponent<RouteComponentProps, State> {
    state: State = {
        isLoading: false,
    };

    submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        const { username, password } = this.state;

        this.setState({ isLoading: true }, () => {
            login(username!, password!)
                .then(response => {
                    if (response.complete) {
                        // @ts-ignore
                        window.location = response.intended || '/';
                        return;
                    }

                    this.props.history.replace('/login/checkpoint', {
                        token: response.confirmationToken,
                    });
                })
                .catch(error => this.setState({
                    isLoading: false,
                    errorMessage: httpErrorToHuman(error),
                }, () => console.error(error)));
        });
    };

    canSubmit () {
        if (!this.state.username || !this.state.password) {
            return false;
        }

        return this.state.username.length > 0 && this.state.password.length > 0;
    }

    // @ts-ignore
    handleFieldUpdate = (e: React.ChangeEvent<HTMLInputElement>) => this.setState({
        [e.target.id]: e.target.value,
    });

    render () {
        return (
            <React.Fragment>
                <h2 className={'text-center text-neutral-100 font-medium py-4'}>
                    Login to Continue
                </h2>
                <NetworkErrorMessage message={this.state.errorMessage}/>
                <form className={'login-box'} onSubmit={this.submit}>
                    <label htmlFor={'username'}>Username or Email</label>
                    <input
                        id={'username'}
                        autoFocus={true}
                        required={true}
                        className={'input'}
                        onChange={this.handleFieldUpdate}
                        disabled={this.state.isLoading}
                    />
                    <div className={'mt-6'}>
                        <label htmlFor={'password'}>Password</label>
                        <input
                            id={'password'}
                            required={true}
                            type={'password'}
                            className={'input'}
                            onChange={this.handleFieldUpdate}
                            disabled={this.state.isLoading}
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
                                'Login'
                            }
                        </button>
                    </div>
                    <div className={'mt-6 text-center'}>
                        <Link
                            to={'/password'}
                            className={'text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600'}
                        >
                            Forgot password?
                        </Link>
                    </div>
                </form>
            </React.Fragment>
        );
    }
}
