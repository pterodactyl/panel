import * as React from 'react';
import ContentBox from '@/components/elements/ContentBox';
import UpdatePasswordForm from '@/components/account/forms/UpdatePasswordForm';

export default () => {
    return (
        <div className={'flex my-10'}>
            <ContentBox className={'flex-1 mr-4'} title={'Update Password'}>
                <UpdatePasswordForm/>
            </ContentBox>
            <div className={'flex-1 ml-4'}>
                <ContentBox title={'Update Email Address'}>
                </ContentBox>
                <ContentBox title={'Update Identity'} className={'mt-8'}>
                </ContentBox>
            </div>
        </div>
    );
};
