import { Checkbox, cn, ErrorBoundary, NewButton, PanelLink } from '@/sdk';
import { Slot, type ViewProps } from '@/sdk/theme';
import Breadcrumbs from './Breadcrumbs';
import { NewDirectoryButton } from './FileDialogs';
import { UploadButton, UploadStatus } from './Uploads';

const managerActionsClass = [
    'grid grid-cols-2 sm:grid-cols-3 w-full gap-4 mb-4',
    'md:flex md:flex-1 md:justify-end md:mb-0',
    '[&_button]:w-full [&_button:first-child]:col-span-2 sm:[&_button:first-child]:col-span-1 md:[&_button]:w-auto',
].join(' ');

export default function FileManagerToolbar({ model, className }: ViewProps<'server.files.manager.toolbar'>) {
    const { entries, selection } = model;

    return (
        <ErrorBoundary>
            <div className={cn('flex flex-wrap-reverse md:flex-nowrap mb-4', className)}>
                <Breadcrumbs crumbs={model.breadcrumbs}>
                    <Checkbox
                        className='mx-4'
                        aria-label='Select all files'
                        checked={entries.length > 0 && selection.length === entries.length}
                        indeterminate={selection.length > 0 && selection.length < entries.length}
                        onChange={(checked) => model.actions.select(checked ? entries.map((entry) => entry.name) : [])}
                    />
                </Breadcrumbs>
                {model.permissions.create && (
                    <div className={managerActionsClass}>
                        <UploadStatus model={model} />
                        <NewDirectoryButton model={model} />
                        {model.permissions.update && <UploadButton model={model} />}
                        <PanelLink destination={model.newFileDestination}>
                            <NewButton>New file</NewButton>
                        </PanelLink>
                    </div>
                )}
            </div>
            <Slot name='server.files.toolbar' data={model.slot} />
        </ErrorBoundary>
    );
}
