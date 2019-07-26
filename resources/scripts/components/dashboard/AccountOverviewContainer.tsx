import * as React from 'react';
import ContentBox from '@/components/elements/ContentBox';
import UpdatePasswordForm from '@/components/dashboard/forms/UpdatePasswordForm';
import UpdateEmailAddressForm from '@/components/dashboard/forms/UpdateEmailAddressForm';

export default () => {
    return (
        <div className={'flex my-10'}>
            <ContentBox className={'flex-1 mr-4'} title={'Update Password'} showFlashes={'account:password'}>
                <UpdatePasswordForm/>
            </ContentBox>
            <ContentBox className={'flex-1 ml-4'} title={'Update Email Address'} showFlashes={'account:email'}>
                <UpdateEmailAddressForm/>
            </ContentBox>
        </div>
    );
};
