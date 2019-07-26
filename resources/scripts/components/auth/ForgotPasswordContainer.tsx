import * as React from 'react';
import { Link } from 'react-router-dom';
import requestPasswordResetEmail from '@/api/auth/requestPasswordResetEmail';
import { httpErrorToHuman } from '@/api/http';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { Actions, useStoreActions } from 'easy-peasy';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';

export default () => {
    const [ isSubmitting, setSubmitting ] = React.useState(false);
    const [ email, setEmail ] = React.useState('');

    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const handleFieldUpdate = (e: React.ChangeEvent<HTMLInputElement>) => setEmail(e.target.value);

    const handleSubmission = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        setSubmitting(true);
        clearFlashes();
        requestPasswordResetEmail(email)
            .then(response => {
                setEmail('');
                addFlash({ type: 'success', title: 'Success', message: response });
            })
            .catch(error => {
                console.error(error);
                addFlash({ type: 'error', title: 'Error', message: httpErrorToHuman(error) });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <div>
            <h2 className={'text-center text-neutral-100 font-medium py-4'}>
                Request Password Reset
            </h2>
            <FlashMessageRender/>
            <LoginFormContainer onSubmit={handleSubmission}>
                <label htmlFor={'email'}>Email</label>
                <input
                    id={'email'}
                    type={'email'}
                    required={true}
                    className={'input'}
                    value={email}
                    onChange={handleFieldUpdate}
                    autoFocus={true}
                />
                <p className={'input-help'}>
                    Enter your account email address to receive instructions on resetting your password.
                </p>
                <div className={'mt-6'}>
                    <button
                        className={'btn btn-primary btn-jumbo flex justify-center'}
                        disabled={isSubmitting || email.length < 5}
                    >
                        {isSubmitting ?
                            <div className={'spinner-circle spinner-sm spinner-white'}></div>
                            :
                            'Send Email'
                        }
                    </button>
                </div>
                <div className={'mt-6 text-center'}>
                    <Link
                        to={'/auth/login'}
                        className={'text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700'}
                    >
                        Return to Login
                    </Link>
                </div>
            </LoginFormContainer>
        </div>
    );
};
