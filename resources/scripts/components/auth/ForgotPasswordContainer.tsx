import * as React from 'react';
import OpenInputField from '@/components/forms/OpenInputField';
import { Link } from 'react-router-dom';
import requestPasswordResetEmail from '@/api/auth/requestPasswordResetEmail';

type Props = Readonly<{

}>;

type State = Readonly<{
    email: string;
    isSubmitting: boolean;
}>;

export default class ForgotPasswordContainer extends React.PureComponent<Props, State> {
    state: State = {
        email: '',
        isSubmitting: false,
    };

    handleFieldUpdate = (e: React.ChangeEvent<HTMLInputElement>) => this.setState({
        email: e.target.value,
    });

    handleSubmission = (e: React.FormEvent<HTMLFormElement>) => this.setState({ isSubmitting: true }, () => {
        e.preventDefault();

        requestPasswordResetEmail(this.state.email)
            .then(() => {

            })
            .catch(console.error)
            .then(() => this.setState({ isSubmitting: false }));
    });

    render () {
        return (
            <React.Fragment>
                <form className={'login-box'} onSubmit={this.handleSubmission}>
                    <div className={'-mx-3'}>
                        <OpenInputField
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
                            className={'btn btn-primary btn-jumbo'}
                            disabled={this.state.isSubmitting || this.state.email.length < 5}
                        >
                            {this.state.isSubmitting ?
                                <span className={'spinner white'}>&nbsp;</span>
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
            </React.Fragment>
        );
    }
}
