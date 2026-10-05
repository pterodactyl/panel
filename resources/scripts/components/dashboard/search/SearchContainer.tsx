import { Search } from 'lucide-react';
import useEventListener from '@/plugins/useEventListener';
import SearchModal from '@/components/dashboard/search/SearchModal';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import Icon from '@/components/elements/Icon';
import { cn } from '@/lib/cn';
import { useDialogState } from '@/components/elements/dialog';

interface Props {
    className?: string;
}

export default function SearchContainer({ className }: Props) {
    const searchDialog = useDialogState();

    useEventListener('keydown', (e: KeyboardEvent) => {
        if (['input', 'textarea'].indexOf(((e.target as HTMLElement).tagName || 'input').toLowerCase()) < 0) {
            if (!searchDialog.open && e.metaKey && e.key.toLowerCase() === '/') {
                searchDialog.show();
            }
        }
    });

    return (
        <>
            {searchDialog.open && <SearchModal open={searchDialog.open} onClose={searchDialog.hide} />}
            <Tooltip placement={'bottom'} content={'Search'}>
                <button
                    type={'button'}
                    aria-label={'Search'}
                    className={cn('navigation-link', className)}
                    onClick={searchDialog.show}
                >
                    <Icon icon={Search} />
                </button>
            </Tooltip>
        </>
    );
}
