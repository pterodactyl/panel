import { PageContentBlock, ServerError } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function SshKeysPage({ model, className }: ViewProps<'page.account.ssh'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} className={className} />;
    }

    return (
        <PageContentBlock title='SSH Keys' className={className}>
            <div className='md:flex flex-nowrap my-10'>
                <View name='account.sshKeys.create' className='flex-none w-full md:w-1/2' />
                <View name='account.sshKeys.list' className='flex-1 overflow-hidden mt-8 md:mt-0 md:ml-8' />
            </div>
        </PageContentBlock>
    );
}
