import * as React from 'react';
import { RouteComponentProps, StaticContext } from 'react-router';
import { connect } from 'react-redux';
import { pushFlashMessage, clearAllFlashMessages } from '@/redux/actions/flash';
import NetworkErrorMessage from '@/components/NetworkErrorMessage';
import MessageBox from '@/components/MessageBox';
import { Link } from 'react-router-dom';
import loginCheckpoint from '@/api/auth/loginCheckpoint';
import { httpErrorToHuman } from '@/api/http';

type State = Readonly<{
    isLoading: boolean;
    errorMessage?: string;
    code: string;
}>;

class LoginCheckpointContainer extends React.PureComponent<RouteComponentProps<{}, StaticContext, { token: string }>, State> {
    state: State = {
        code: '',
        isLoading: false,
    };

    componentDidMount () {
        const { state } = this.props.location;
        if (!state || !state.token) {
            this.props.history.replace('/login');
        }
    }

    onChangeHandler = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.value.length > 6) {
            e.target.value = e.target.value.substring(0, 6);
            return e.preventDefault();
        }

        this.setState({ code: e.target.value });
    };

    submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        this.setState({ isLoading: true }, () => {
            loginCheckpoint(this.props.location.state.token, this.state.code)
                .then(response => {
                    if (response.complete) {
                        // @ts-ignore
                        window.location = response.intended || '/';
                    }
                })
                .catch(error => {
                    console.error(error);
                    this.setState({ errorMessage: httpErrorToHuman(error), isLoading: false });
                });
        });
    };

    render () {
        return (
            <React.Fragment>
                <h2 className={'text-center text-neutral-100 font-medium py-4'}>
                    Device Checkpoint
                </h2>
                <NetworkErrorMessage message={this.state.errorMessage}/>
                <form className={'login-box'} onSubmit={this.submit}>
                    <MessageBox type={'warning'}>
                        This account is protected with two-factor authentication. A valid authentication token must
                        be provided in order to continue.
                    </MessageBox>
                    <div className={'mt-6'}>
                        <label htmlFor={'authentication_code'}>Authentication Code</label>
                        <input
                            id={'authentication_code'}
                            type={'number'}
                            autoFocus={true}
                            className={'input'}
                            onChange={this.onChangeHandler}
                        />
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
                    <div className={'mt-6 text-center'}>
                        <Link
                            to={'/login'}
                            className={'text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700'}
                        >
                            Return to Login
                        </Link>
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
