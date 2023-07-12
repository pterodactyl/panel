import { useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faSearch } from '@fortawesome/free-solid-svg-icons';
import useEventListener from '@/plugins/useEventListener';
import SearchModal from '@/components/dashboard/search/SearchModal';
import Tooltip from '@/components/elements/tooltip/Tooltip';

export default () => {
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
            <SearchModal appear visible={visible} onDismissed={() => setVisible(false)} />

            <Tooltip placement={'bottom'} content={'Search'}>
                <div className={'navigation-link'} onClick={() => setVisible(true)}>
                    <FontAwesomeIcon icon={faSearch} />
                </div>
            </Tooltip>
        </>
    );
};
