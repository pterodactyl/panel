import React, { useState } from 'react';
import { RouteComponentProps } from 'react-router';
import { parse } from 'query-string';
import { Link } from 'react-router-dom';
import performPasswordReset from '@/api/auth/performPasswordReset';
import { httpErrorToHuman } from '@/api/http';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';

type Props = Readonly<RouteComponentProps<{ token: string }> & {}>;

export default (props: Props) => {
    const [ isLoading, setIsLoading ] = useState(false);
    const [ email, setEmail ] = useState('');
    const [ password, setPassword ] = useState('');
    const [ passwordConfirm, setPasswordConfirm ] = useState('');

    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const parsed = parse(props.location.search);
    if (email.length === 0 && parsed.email) {
        setEmail(parsed.email as string);
    }

    const canSubmit = () => password && email && password.length >= 8 && password === passwordConfirm;

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        if (!password || !email || !passwordConfirm) {
            return;
        }

        setIsLoading(true);
        clearFlashes();

        performPasswordReset(email, {
            token: props.match.params.token, password, passwordConfirmation: passwordConfirm,
        })
            .then(() => {
                addFlash({ type: 'success', message: 'Your password has been reset, please login to continue.' });
                props.history.push('/auth/login');
            })
            .catch(error => {
                console.error(error);
                addFlash({ type: 'error', title: 'Error', message: httpErrorToHuman(error) });
            })
            .then(() => setIsLoading(false));
    };

    return (
        <div>
            <h2 className={'text-center text-neutral-100 font-medium py-4'}>
                Reset Password
            </h2>
            <FlashMessageRender/>
            <LoginFormContainer onSubmit={submit}>
                <label>Email</label>
                <input className={'input'} value={email} disabled={true}/>
                <div className={'mt-6'}>
                    <label htmlFor={'new_password'}>New Password</label>
                    <input
                        id={'new_password'}
                        className={'input'}
                        type={'password'}
                        required={true}
                        onChange={e => setPassword(e.target.value)}
                    />
                    <p className={'input-help'}>
                        Passwords must be at least 8 characters in length.
                    </p>
                </div>
                <div className={'mt-6'}>
                    <label htmlFor={'new_password_confirm'}>Confirm New Password</label>
                    <input
                        id={'new_password_confirm'}
                        className={'input'}
                        type={'password'}
                        required={true}
                        onChange={e => setPasswordConfirm(e.target.value)}
                    />
                </div>
                <div className={'mt-6'}>
                    <button
                        type={'submit'}
                        className={'btn btn-primary btn-jumbo'}
                        disabled={isLoading || !canSubmit()}
                    >
                        {isLoading ?
                            <span className={'spinner white'}>&nbsp;</span>
                            :
                            'Reset Password'
                        }
                    </button>
                </div>
                <div className={'mt-6 text-center'}>
                    <Link
                        to={'/auth/login'}
                        className={'text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600'}
                    >
                        Return to Login
                    </Link>
                </div>
            </LoginFormContainer>
        </div>
    );
};
