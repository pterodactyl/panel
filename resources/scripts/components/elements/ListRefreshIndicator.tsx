import React from 'react';
import Spinner from '@/components/elements/Spinner';
import { CSSTransition } from 'react-transition-group';

interface Props {
    visible: boolean;
    children?: React.ReactChild;
}

const ListRefreshIndicator = ({ visible, children }: Props) => (
    <CSSTransition timeout={250} in={visible} appear={true} unmountOnExit={true} classNames={'fade'}>
        <div className={'flex items-center mb-2'}>
            <Spinner size={'tiny'}/>
            <p className={'ml-2 text-sm text-neutral-400'}>{children || 'Refreshing listing...'}</p>
        </div>
    </CSSTransition>
);

export default ListRefreshIndicator;
