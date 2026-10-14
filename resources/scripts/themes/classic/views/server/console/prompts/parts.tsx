import { TriangleAlert } from 'lucide-react';
import { Button, Icon } from '@/sdk';

export const cancelClass = 'w-full sm:w-auto border-transparent';

export const Close = ({ onClick }: { onClick: () => void }) => (
    <div className='mt-8 sm:flex items-center justify-end'>
        <Button onClick={onClick} className={cancelClass}>
            Close
        </Button>
    </div>
);

export const LimitIcon = () => (
    <div className='mt-4 sm:flex items-center'>
        <Icon icon={TriangleAlert} className='pr-4 text-warning' size={64} />
    </div>
);
