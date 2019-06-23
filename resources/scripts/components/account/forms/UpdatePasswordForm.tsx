import React, { useState } from 'react';
import { State, useStoreState } from 'easy-peasy';
import { ApplicationState } from '@/state/types';

export default () => {
    const [ isLoading, setIsLoading ] = useState(false);
    const user = useStoreState((state: State<ApplicationState>) => state.user.data);

    if (!user) {
        return null;
    }

    return (
        <form className={'m-0'}>
            <label htmlFor={'current_password'} className={'input-dark-label'}>Current Password</label>
            <input
                id={'current_password'}
                type={'password'}
                className={'input-dark'}
            />
            <div className={'mt-6'}>
                <label htmlFor={'new_password'} className={'input-dark-label'}>New Password</label>
                <input
                    id={'new_password'}
                    type={'password'}
                    className={'input-dark'}
                />
                <p className={'input-help'}>
                    Your new password must be at least 8 characters in length.
                </p>
            </div>
            <div className={'mt-6'}>
                <label htmlFor={'new_password_confirm'} className={'input-dark-label'}>Confirm New Password</label>
                <input
                    id={'new_password_confirm'}
                    type={'password'}
                    className={'input-dark'}
                />
            </div>
            <div className={'mt-6'}>
                <button className={'btn btn-primary btn-sm'} disabled={true}>
                    Update Password
                </button>
            </div>
        </form>
    );
};
