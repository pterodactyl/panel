import { useState } from 'react';
import { ClipboardListIcon } from '@heroicons/react/outline';
import { Dialog } from '@/components/elements/dialog';
import { Button } from '@/components/elements/button/index';

export default ({ meta }: { meta: Record<string, unknown> }) => {
    const [open, setOpen] = useState(false);

    return (
        <div className={'self-center md:px-4'}>
            <Dialog open={open} onClose={() => setOpen(false)} hideCloseIcon title={'Metadata'}>
                <pre
                    className={
                        'overflow-x-scroll whitespace-pre-wrap rounded bg-slate-900 p-2 font-mono text-sm leading-relaxed'
                    }
                >
                    {JSON.stringify(meta, null, 2)}
                </pre>
                <Dialog.Footer>
                    <Button.Text onClick={() => setOpen(false)}>Close</Button.Text>
                </Dialog.Footer>
            </Dialog>
            <button
                aria-describedby={'View additional event metadata'}
                className={
                    'p-2 text-slate-400 transition-colors duration-100 group-hover:text-slate-300 group-hover:hover:text-slate-50'
                }
                onClick={() => setOpen(true)}
            >
                <ClipboardListIcon className={'h-5 w-5'} />
            </button>
        </div>
    );
};
