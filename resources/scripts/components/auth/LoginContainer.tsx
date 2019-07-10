import React, { useState } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import login from '@/api/auth/login';
import { httpErrorToHuman } from '@/api/http';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';

export default ({ history }: RouteComponentProps) => {
    const [ username, setUsername ] = useState('');
    const [ password, setPassword ] = useState('');
    const [ isLoading, setLoading ] = useState(false);

    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        setLoading(true);
        clearFlashes();

        login(username!, password!)
            .then(response => {
                if (response.complete) {
                    // @ts-ignore
                    window.location = response.intended || '/';
                    return;
                }

                history.replace('/auth/login/checkpoint', { token: response.confirmationToken });
            })
            .catch(error => {
                console.error(error);

                setLoading(false);
                addFlash({ type: 'error', title: 'Error', message: httpErrorToHuman(error) });
            });
    };

    const canSubmit = () => username && password && username.length > 0 && password.length > 0;

    return (
        <React.Fragment>
            <h2 className={'text-center text-neutral-100 font-medium py-4'}>
                Login to Continue
            </h2>
            <FlashMessageRender/>
            <LoginFormContainer onSubmit={submit}>
                <label htmlFor={'username'}>Username or Email</label>
                <input
                    id={'username'}
                    autoFocus={true}
                    required={true}
                    className={'input'}
                    onChange={e => setUsername(e.target.value)}
                    disabled={isLoading}
                />
                <div className={'mt-6'}>
                    <label htmlFor={'password'}>Password</label>
                    <input
                        id={'password'}
                        required={true}
                        type={'password'}
                        className={'input'}
                        onChange={e => setPassword(e.target.value)}
                        disabled={isLoading}
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
                            'Login'
                        }
                    </button>
                </div>
                <div className={'mt-6 text-center'}>
                    <Link
                        to={'/auth/password'}
                        className={'text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600'}
                    >
                        Forgot password?
                    </Link>
                </div>
            </LoginFormContainer>
        </React.Fragment>
    );
};
