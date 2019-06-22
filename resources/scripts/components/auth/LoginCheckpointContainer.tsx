import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import loginCheckpoint from '@/api/auth/loginCheckpoint';
import { httpErrorToHuman } from '@/api/http';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationState } from '@/state/types';
import useRouter from 'use-react-router';
import { StaticContext } from 'react-router';
import FlashMessageRender from '@/components/FlashMessageRender';

export default () => {
    const [ code, setCode ] = useState('');
    const [ isLoading, setIsLoading ] = useState(false);

    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationState>) => actions.flashes);
    const { history, location: { state } } = useRouter<{}, StaticContext, { token?: string }>();

    if (!state || !state.token) {
        return history.replace('/login');
    }

    const onChangeHandler = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.value.length <= 6) {
            setCode(e.target.value);
        }
    };

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        setIsLoading(true);
        clearFlashes();

        loginCheckpoint(state.token!, code)
            .then(response => {
                if (response.complete) {
                    // @ts-ignore
                    window.location = response.intended || '/';
                }
            })
            .catch(error => {
                console.error(error);
                addFlash({ type: 'error', title: 'Error', message: httpErrorToHuman(error) });
                setIsLoading(false);
            });
    };

    return (
        <React.Fragment>
            <h2 className={'text-center text-neutral-100 font-medium py-4'}>
                Device Checkpoint
            </h2>
            <FlashMessageRender/>
            <LoginFormContainer onSubmit={submit}>
                <div className={'mt-6'}>
                    <label htmlFor={'authentication_code'}>Authentication Code</label>
                    <input
                        id={'authentication_code'}
                        type={'number'}
                        autoFocus={true}
                        className={'input'}
                        value={code}
                        onChange={onChangeHandler}
                    />
                </div>
                <div className={'mt-6'}>
                    <button
                        type={'submit'}
                        className={'btn btn-primary btn-jumbo'}
                        disabled={isLoading || code.length !== 6}
                    >
                        {isLoading ?
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
            </LoginFormContainer>
        </React.Fragment>
    );
};
