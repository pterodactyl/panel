import { PageContentBlock } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../shared/PageStatus';

export default function ApiKeysPage({ model, className }: ViewProps<'page.account.api'>) {
    return (
        <PageStatus model={model} className={className}>
            <PageContentBlock title='Account API' className={className}>
                <div className='md:flex flex-nowrap my-10'>
                    <View name='account.apiKeys.create' className='flex-none w-full md:w-1/2' />
                    <View name='account.apiKeys.list' className='flex-1 overflow-hidden mt-8 md:mt-0 md:ml-8' />
                </div>
            </PageContentBlock>
        </PageStatus>
    );
}
