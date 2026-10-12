import { ErrorBoundary, PageContentBlock, ServerError, Spinner } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import Breadcrumbs from './Breadcrumbs';

export default function EditorPage({ model, className }: ViewProps<'page.server.files.editor'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onBack={model.back} className={className} />;
    }

    return (
        <PageContentBlock className={className}>
            <ErrorBoundary>
                <div className='mb-4'>
                    <Breadcrumbs crumbs={model.breadcrumbs} file={model.file} />
                </div>
            </ErrorBoundary>
            {model.loading ? <Spinner size='large' centered /> : <View name='server.files.editor' />}
        </PageContentBlock>
    );
}
