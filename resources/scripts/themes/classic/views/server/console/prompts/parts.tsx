import { TriangleAlert } from 'lucide-react';
import { Button, Icon, useTranslation } from '@/sdk';

export const cancelClass = 'w-full sm:w-auto border-transparent';

export const Close = ({ onClick }: { onClick: () => void }) => {
    const { t } = useTranslation();

    return (
        <div className='mt-8 sm:flex items-center justify-end'>
            <Button onClick={onClick} className={cancelClass}>
                {t('Close')}
            </Button>
        </div>
    );
};

export const LimitIcon = () => (
    <div className='mt-4 sm:flex items-center'>
        <Icon icon={TriangleAlert} className='pr-4 text-warning' size={64} />
    </div>
);
