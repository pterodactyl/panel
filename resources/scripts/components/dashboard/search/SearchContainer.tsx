import React, { useState } from 'react';
import useEventListener from '@/plugins/useEventListener';
import { faSearch } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import SearchModal from '@/components/dashboard/search/SearchModal';

export default () => {
    const [ visible, setVisible ] = useState(false);

    useEventListener('keydown', (e: KeyboardEvent) => {
        if ([ 'input', 'textarea' ].indexOf(((e.target as HTMLElement).tagName || 'input').toLowerCase()) < 0) {
            if (!visible && e.metaKey && e.key.toLowerCase() === '/') {
                setVisible(true);
            }
        }
    });

    return (
        <>
            {visible &&
            <SearchModal
                appear
                visible={visible}
                onDismissed={() => setVisible(false)}
            />
            }
            <div className={'navigation-link'} onClick={() => setVisible(true)}>
                <FontAwesomeIcon icon={faSearch}/>
            </div>
        </>
    );
};
