import React from 'react';
import ContentBox from '@/components/elements/ContentBox';
import CreateApiKeyForm from '@/components/dashboard/forms/CreateApiKeyForm';

export default () => {
    return (
        <div className={'my-10 flex'}>
            <ContentBox title={'Create API Key'} className={'flex-1'} showFlashes={'account'}>
                <CreateApiKeyForm/>
            </ContentBox>
            <ContentBox title={'API Keys'} className={'ml-10 flex-1'}>
                <p>Testing</p>
            </ContentBox>
        </div>
    );
};
