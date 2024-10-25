import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faBell, faQuestionCircle } from '@fortawesome/free-solid-svg-icons';
import Tooltip from '@/components/elements/tooltip/Tooltip';

export default () => {
    function validateDate(year: number, month: number, day: number): boolean {
        let inputDate = new Date(year, month - 1, day);
        return new Date(inputDate.toDateString()) >= new Date(new Date().toDateString());
    }

    if (validateDate(2024, 10, 12)) {
        return (
            <>
                <Tooltip placement={'bottom'} content={'Geplante Wartungsarbeiten'}>
                    <div className={'navigation-link'} onClick={() => window.open('https://status.arion2000.xyz/incident/962664', '_blank')}>
                        <FontAwesomeIcon icon={faBell} />
                    </div>
                </Tooltip>
            </>
        )
    } else {
        return (
            <>
                <Tooltip placement={'bottom'} content={'Hilfe'}>
                    <div className={'navigation-link'} onClick={() => window.open('https://docs.arion2000.xyz', '_blank')}>
                        <FontAwesomeIcon icon={faQuestionCircle} />
                    </div>
                </Tooltip>
            </>
        );
    }
};
