import { PageContentBlock, ServerError } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function ApiKeysPage({ model }: ViewProps<'page.account.api'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} />;
    }

    return (
        <PageContentBlock title='Account API'>
            <div className='md:flex flex-nowrap my-10'>
                <View name='account.apiKeys.create' className='flex-none w-full md:w-1/2' />
                <View name='account.apiKeys.list' className='flex-1 overflow-hidden mt-8 md:mt-0 md:ml-8' />
            </div>
        </PageContentBlock>
    );
}
