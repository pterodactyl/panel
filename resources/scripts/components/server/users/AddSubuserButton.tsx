import React, { useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUserPlus } from '@fortawesome/free-solid-svg-icons';
import EditSubuserModal from '@/components/server/users/EditSubuserModal';
import Button from '@/components/elements/Button';
import tw from 'twin.macro';
import { useTranslation } from 'react-i18next';

export default () => {
    const { t } = useTranslation();
    const [ visible, setVisible ] = useState(false);

    return (
        <>
            <EditSubuserModal visible={visible} onModalDismissed={() => setVisible(false)}/>
            <Button onClick={() => setVisible(true)}>
                <FontAwesomeIcon icon={faUserPlus} css={tw`mr-1`}/> {t('Users New User Button')}
            </Button>
        </>
    );
};
