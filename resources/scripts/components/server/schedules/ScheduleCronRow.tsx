import { type Schedule } from '@/api/server/schedules/queries';
import { cn } from '@/lib/cn';

interface Props {
    cron: Schedule['attributes']['cron'];
    className?: string;
}

const ScheduleCronRow = ({ cron, className }: Props) => (
    <div className={cn('flex', className)}>
        <div className='w-1/5 sm:w-auto text-center'>
            <p className='font-medium'>{cron.minute}</p>
            <p className='text-xs text-muted-foreground uppercase'>Minute</p>
        </div>
        <div className='w-1/5 sm:w-auto text-center ml-4'>
            <p className='font-medium'>{cron.hour}</p>
            <p className='text-xs text-muted-foreground uppercase'>Hour</p>
        </div>
        <div className='w-1/5 sm:w-auto text-center ml-4'>
            <p className='font-medium'>{cron.day_of_month}</p>
            <p className='text-xs text-muted-foreground uppercase'>Day (Month)</p>
        </div>
        <div className='w-1/5 sm:w-auto text-center ml-4'>
            <p className='font-medium'>{cron.month}</p>
            <p className='text-xs text-muted-foreground uppercase'>Month</p>
        </div>
        <div className='w-1/5 sm:w-auto text-center ml-4'>
            <p className='font-medium'>{cron.day_of_week}</p>
            <p className='text-xs text-muted-foreground uppercase'>Day (Week)</p>
        </div>
    </div>
);

export default ScheduleCronRow;
