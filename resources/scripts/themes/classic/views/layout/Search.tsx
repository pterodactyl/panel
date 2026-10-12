import { Search as SearchIcon, SearchX } from 'lucide-react';
import {
    Alert,
    cn,
    Dialog,
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    Icon,
    InputSpinner,
    PanelLink,
    Tooltip,
} from '@/sdk';
import type { AppSearchModel, ViewProps } from '@/sdk/theme';
import { TextField } from '../shared/fields';

const resultClass = [
    'flex items-center bg-muted p-4 rounded-sm border-l-4 border-border no-underline transition-[background-color,border-color,color] duration-150',
    'hover:shadow-sm hover:border-accent',
    'not-last-of-type:mb-2',
].join(' ');

function Results({ model }: { model: AppSearchModel }) {
    if (model.error) {
        return (
            <div className='mt-4'>
                <Alert type='danger'>{model.error}</Alert>
            </div>
        );
    }

    if (model.searched && model.results.length === 0) {
        return (
            <Empty size='compact' className='mt-6'>
                <EmptyHeader>
                    <EmptyMedia variant='icon'>
                        <SearchX />
                    </EmptyMedia>
                    <EmptyTitle>No servers found</EmptyTitle>
                    <EmptyDescription>No servers match your search. Try a different name or address.</EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    return model.results.length > 0 ? (
        <div className='mt-6'>
            {model.results.map((result) => (
                <PanelLink
                    key={result.uuid}
                    destination={result.destination}
                    className={resultClass}
                    onClick={model.close}
                >
                    <div className='flex-1 mr-4'>
                        <p className='text-sm'>{result.name}</p>
                        <p className='mt-1 text-xs text-muted-foreground'>
                            {result.addresses.map((address) => (
                                <span key={address}>{address}</span>
                            ))}
                        </p>
                    </div>
                    <div className='flex-none text-right'>
                        <span className='text-xs py-1 px-2 bg-accent text-accent-foreground rounded-sm'>
                            {result.node}
                        </span>
                    </div>
                </PanelLink>
            ))}
        </div>
    ) : null;
}

export default function Search({ model, className }: ViewProps<'app.search'>) {
    return (
        <>
            {model.open && (
                <Dialog title='Search servers' open onClose={model.close}>
                    <form noValidate onSubmit={(event) => event.preventDefault()}>
                        <InputSpinner visible={model.loading}>
                            <TextField
                                field={model.field}
                                label='Search term'
                                description='Enter a server name, uuid, or allocation to begin searching.'
                                autoFocus
                            />
                        </InputSpinner>
                    </form>
                    <Results model={model} />
                </Dialog>
            )}
            <Tooltip placement='bottom' content='Search'>
                <button
                    type='button'
                    aria-label='Search'
                    className={cn('navigation-link', className)}
                    onClick={model.show}
                >
                    <Icon icon={SearchIcon} />
                </button>
            </Tooltip>
        </>
    );
}
