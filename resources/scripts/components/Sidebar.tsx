import React, { ReactNode } from 'react';
import '@/assets/css/sidebar.css';

type ParentProps = {
    children: ReactNode;
};

export default ({ children }: Omit<ParentProps, 'render'>) => {
    return (
        <>
            <div className='sidebar' id='sidebar'>
                {children}
            </div>
        </>
    );
};
