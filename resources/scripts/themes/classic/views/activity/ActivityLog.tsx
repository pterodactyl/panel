import { History, ListFilter, XCircle } from 'lucide-react';
import {
    Alert,
    ButtonStyle,
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    NewButton,
    PaginationFooter,
    Select,
    Spinner,
    useTranslation,
    type ActivityLogModel,
    type ActivityScope,
    type Translation,
} from '@/sdk';
import type { ViewProps } from '@/sdk/theme';
import ActivityLogEntry from './ActivityLogEntry';

const emptyMessages = (t: Translation['t']) =>
    ({
        account: t('Actions taken on your account will appear here.'),
        server: t('Actions taken on this server will appear here.'),
        admin: t('Actions taken through the admin panel will appear here.'),
    }) satisfies Record<ActivityScope, string>;

function Results({ model, filtered }: { model: ActivityLogModel; filtered: boolean }) {
    const { t } = useTranslation();

    if (model.loading) {
        return <Spinner centered />;
    }

    if (model.entries.length) {
        return (
            <div className='bg-card'>
                {model.entries.map((entry) => (
                    <ActivityLogEntry key={entry.id} entry={entry} />
                ))}
            </div>
        );
    }

    return (
        <Empty className='border bg-card'>
            <EmptyHeader>
                <EmptyMedia variant='icon'>{filtered ? <ListFilter /> : <History />}</EmptyMedia>
                <EmptyTitle>{filtered ? t('No matching activity') : t('No activity yet')}</EmptyTitle>
                <EmptyDescription>
                    {filtered ? t('No activity matches the selected filters.') : emptyMessages(t)[model.scope]}
                </EmptyDescription>
            </EmptyHeader>
            {filtered && (
                <EmptyContent>
                    <NewButton isSecondary icon={XCircle} onClick={model.actions.clearFilters}>
                        {t('Clear filters')}
                    </NewButton>
                </EmptyContent>
            )}
        </Empty>
    );
}

function Filters({ model, filtered }: { model: ActivityLogModel; filtered: boolean }) {
    const { actions } = model;
    const { t } = useTranslation();

    return (
        <div className='mb-3 flex flex-col gap-2 sm:flex-row sm:items-stretch'>
            {model.users && (
                <div className='w-full sm:max-w-xs'>
                    <Select
                        options={[{ value: '', label: t('All users') }, ...model.users]}
                        value={model.user ?? ''}
                        placeholder={t('All users')}
                        searchPlaceholder={t('Search users…')}
                        emptyMessage={t('No users have acted here yet.')}
                        onChange={(value) => actions.setUser(String(value) || null)}
                    />
                </div>
            )}
            <div className='w-full sm:max-w-sm'>
                <Select
                    groups={[
                        { label: '', options: [{ value: '', label: t('All events') }] },
                        ...model.events.map((group) => ({ label: group.label, options: [...group.options] })),
                    ]}
                    value={model.event ?? ''}
                    placeholder={t('All events')}
                    searchPlaceholder={t('Search events…')}
                    emptyMessage={t('Nothing has been recorded here yet.')}
                    onChange={(value) => actions.setEvent(String(value) || null)}
                />
            </div>
            {filtered && (
                <ButtonStyle
                    type='button'
                    color='grey'
                    className='inline-flex items-center justify-center w-full sm:w-auto sm:shrink-0 h-auto'
                    onClick={actions.clearFilters}
                >
                    {t('Clear Filters')} <XCircle className='w-4 h-4 ml-2' />
                </ButtonStyle>
            )}
        </div>
    );
}

export default function ActivityLog({ model, className }: ViewProps<'activity.log'>) {
    const filtered = model.event !== null || model.user !== null;
    const { t } = useTranslation();

    return (
        <div className={className}>
            {model.error !== null && (
                <div className='mb-4 space-y-3'>
                    <Alert type='danger'>{model.error}</Alert>
                    <ButtonStyle type='button' color='grey' onClick={model.retry}>
                        {t('Retry')}
                    </ButtonStyle>
                </div>
            )}
            <Filters model={model} filtered={filtered} />
            <Results model={model} filtered={filtered} />
            {model.pagination && (
                <PaginationFooter pagination={model.pagination} onPageSelect={model.actions.setPage} />
            )}
        </div>
    );
}
