import * as React from 'react';
import OpenInputField from '@/components/forms/OpenInputField';
import { Link } from 'react-router-dom';
import login from '@/api/auth/login';
import { httpErrorToHuman } from '@/api/http';
import MessageBox from '@/components/MessageBox';

type State = Readonly<{
    errorMessage?: string;
    isLoading: boolean;
    username?: string;
    password?: string;
}>;

export default class LoginContainer extends React.PureComponent<{}, State> {
    username = React.createRef<HTMLInputElement>();

    state: State = {
        isLoading: false,
    };

    submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        const { username, password } = this.state;

        this.setState({ isLoading: true }, () => {
            login(username!, password!)
                .then(response => {

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
                {this.state.errorMessage &&
                <div className={'mb-4'}>
                    <MessageBox type={'error'} title={'Error'}>
                        {this.state.errorMessage}
                    </MessageBox>
                </div>
                }
                <form className={'login-box'} onSubmit={this.submit}>
                    <div className={'-mx-3'}>
                        <OpenInputField
                            autoFocus={true}
                            label={'Username or Email'}
                            type={'text'}
                            required={true}
                            id={'username'}
                            onChange={this.handleFieldUpdate}
                            disabled={this.state.isLoading}
                        />
                    </div>
                    <div className={'-mx-3 mt-6'}>
                        <OpenInputField
                            label={'Password'}
                            type={'password'}
                            required={true}
                            id={'password'}
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
                            to={'/forgot-password'}
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
