import * as Icon from 'react-feather';
import React, { useState } from 'react';
import useEventListener from '@/plugins/useEventListener';
import SearchModal from '@/components/dashboard/search/SearchModal';
import Tooltip from '@/components/elements/tooltip/Tooltip';

interface Props {
    size: number;
}

export default ({ size }: Props) => {
    const [visible, setVisible] = useState(false);

    useEventListener('keydown', (e: KeyboardEvent) => {
        if (['input', 'textarea'].indexOf(((e.target as HTMLElement).tagName || 'input').toLowerCase()) < 0) {
            if (!visible && e.metaKey && e.key.toLowerCase() === '/') {
                setVisible(true);
            }
        }
    });

    return (
        <>
            {visible && <SearchModal appear visible={visible} onDismissed={() => setVisible(false)} />}
            <Tooltip placement={'bottom'} content={'Search'}>
                <div className={'navigation-link'} onClick={() => setVisible(true)}>
                    <Icon.Search size={size} />
                </div>
            </Tooltip>
        </>
    );
};
