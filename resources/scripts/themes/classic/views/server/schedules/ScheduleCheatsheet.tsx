import { CircleHelp } from 'lucide-react';
import { cn, Icon, Tooltip, useTranslation, type Translation } from '@/sdk';

interface Row {
    token: string;
    meaning: string;
    detail?: string;
}

const examples = (t: Translation['t']): Row[] => [
    { token: '*/5 * * * *', meaning: t('every 5 minutes') },
    { token: '0 */1 * * *', meaning: t('every hour') },
    { token: '0 8-12 * * *', meaning: t('hour range') },
    { token: '0,30 * * * *', meaning: t('on the hour and half past') },
    { token: '0 0 * * *', meaning: t('once a day') },
    { token: '0 0 * * MON', meaning: t('every Monday') },
    { token: '0 0 * * MON,FRI', meaning: t('every Monday and Friday') },
];

const specialCharacters = (t: Translation['t']): Row[] => [
    {
        token: '*',
        meaning: t('any value'),
        detail: t('Matches every value in that field. An hour of * runs the task every hour.'),
    },
    {
        token: ',',
        meaning: t('value list separator'),
        detail: t('Picks several exact values. A minute of 0,30 runs on the hour and at half past.'),
    },
    {
        token: '-',
        meaning: t('range values'),
        detail: t('Covers everything between two values. An hour of 8-12 runs at 8, 9, 10, 11 and 12.'),
    },
    {
        token: '/',
        meaning: t('step values'),
        detail: t('Repeats every nth value. A minute of */15 runs four times an hour.'),
    },
];

const hintClass = [
    'inline-flex cursor-help items-baseline gap-1.5 text-left',
    'underline decoration-dotted decoration-muted-foreground underline-offset-4',
    'hover:text-foreground hover:decoration-foreground',
    'focus-visible:text-foreground focus-visible:decoration-foreground focus-visible:outline-hidden',
].join(' ');

const RowMeaning = ({ meaning, detail }: Row) =>
    detail ? (
        <Tooltip content={detail} interactions={['hover', 'focus', 'click']}>
            <button type='button' className={hintClass}>
                {meaning}
                <Icon icon={CircleHelp} className='shrink-0 self-center text-muted-foreground' />
            </button>
        </Tooltip>
    ) : (
        meaning
    );

const CheatsheetCard = ({ title, rows, tokenClass }: { title: string; rows: Row[]; tokenClass: string }) => (
    <div className='overflow-hidden rounded-sm border border-border bg-background/50'>
        <h3 className='border-b border-border bg-popover px-3 py-2 text-xs font-semibold uppercase text-muted-foreground'>
            {title}
        </h3>
        <dl className='divide-y divide-border text-xs'>
            {rows.map((row) => (
                <div key={row.token} className='flex items-baseline gap-3 px-3 py-2.5'>
                    <dt className={cn('shrink-0 whitespace-nowrap font-mono text-foreground', tokenClass)}>
                        {row.token}
                    </dt>
                    <dd className='min-w-0 text-card-foreground'>
                        <RowMeaning {...row} />
                    </dd>
                </div>
            ))}
        </dl>
    </div>
);

/** Examples of cron syntax and what its special characters mean. */
export default function ScheduleCheatsheet() {
    const { t } = useTranslation();

    return (
        <div className='mt-4 grid items-start gap-4 sm:grid-cols-2'>
            <CheatsheetCard title={t('Examples')} rows={examples(t)} tokenClass='w-32' />
            <CheatsheetCard title={t('Special characters')} rows={specialCharacters(t)} tokenClass='w-6 text-center' />
        </div>
    );
}
