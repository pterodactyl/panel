import { FolderOpen, Monitor, Terminal } from 'lucide-react';
import {
    Avatar,
    cn,
    formatDate,
    fromNow,
    PanelLink,
    Tooltip,
    Translate,
    useTranslation,
    type ActivityEntry,
    type ActivityProperties,
    type TranslationValue,
    type TranslationValues,
} from '@/sdk';
import ActivityMetadataButton from './ActivityMetadataButton';

const activityIconClass =
    'flex space-x-1 mx-2 transition-colors duration-100 text-muted-foreground [&_svg]:px-1 [&_svg]:py-px [&_svg]:cursor-pointer [&_svg]:h-5 [&_svg]:w-auto [&_svg]:hover:text-foreground';

const activityDescriptionClass =
    'mt-1 text-sm break-words line-clamp-2 pr-4 [&_strong]:break-all [&_strong]:font-semibold [&_strong]:text-foreground';

const isCount = (key: string): boolean => key === 'count' || key.endsWith('_count');

// Every value is emphasised as text, apart from the counts the plurals read.
function textProperty(value: TranslationValue): TranslationValue {
    if (Array.isArray(value)) {
        return value.map(textProperty);
    }

    if (value === true || value === false) {
        return value;
    }

    if (value instanceof Object) {
        return Object.fromEntries(
            Object.entries(value).map(([key, item]) => [key, isCount(key) ? item : textProperty(item)])
        );
    }

    return String(value);
}

const textProperties = (properties: ActivityProperties): TranslationValues =>
    Object.fromEntries(
        Object.entries(properties).map(([key, value]) => [key, isCount(key) ? value : textProperty(value)])
    );

export default function ActivityLogEntry({ entry }: { entry: ActivityEntry }) {
    const { actor } = entry;
    const { t } = useTranslation();

    return (
        <div className='grid grid-cols-10 py-4 border-b-2 border-border last:rounded-b-sm last:border-0 group'>
            <div className='hidden sm:flex sm:col-span-1 items-center justify-center select-none'>
                <div className='flex items-center w-10 h-10 rounded-full bg-popover overflow-hidden'>
                    <Avatar name={actor?.username || actor?.email || 'system'} image={actor?.avatar} />
                </div>
            </div>
            <div className='col-span-10 sm:col-span-9 flex'>
                <div className='flex-1 px-4 sm:px-0'>
                    <div className='flex items-center text-foreground'>
                        <Tooltip placement='top' content={actor?.email || t('System User')}>
                            <span>{actor?.username || t('System')}</span>
                        </Tooltip>
                        <span className='text-muted-foreground'>&nbsp;&mdash;&nbsp;</span>
                        <PanelLink
                            destination={entry.filter}
                            includeSearch
                            className='transition-colors duration-75 active:text-accent hover:text-accent'
                        >
                            {entry.event}
                        </PanelLink>
                        <div className={cn(activityIconClass, 'group-hover:text-muted-foreground')}>
                            {entry.api && (
                                <Tooltip placement='top' content={t('Using API Key')}>
                                    <Terminal />
                                </Tooltip>
                            )}
                            {entry.sftp && (
                                <Tooltip placement='top' content={t('Using SFTP')}>
                                    <FolderOpen />
                                </Tooltip>
                            )}
                            {entry.userAgent !== null && (
                                <Tooltip content={entry.userAgent} placement='top'>
                                    <span>
                                        <Monitor />
                                    </span>
                                </Tooltip>
                            )}
                        </div>
                    </div>
                    <p className={activityDescriptionClass}>
                        <Translate
                            ns='activity'
                            values={textProperties(entry.properties)}
                            valueComponent={<strong />}
                            i18nKey={entry.event.replace(':', '.')}
                        />
                    </p>
                    <div className='mt-1 flex items-center text-sm'>
                        {entry.ip && (
                            <span>
                                {entry.ip}
                                <span className='text-muted-foreground'>&nbsp;|&nbsp;</span>
                            </span>
                        )}
                        <Tooltip placement='right' content={formatDate(entry.timestamp, 'll LTS')}>
                            <span>{fromNow(entry.timestamp)}</span>
                        </Tooltip>
                    </div>
                </div>
                {entry.hasMetadata && <ActivityMetadataButton meta={entry.properties} />}
            </div>
        </div>
    );
}
