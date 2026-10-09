import React from 'react';

const DialogFooter = ({ children }: { children: React.ReactNode }) => (
    <div className='px-6 py-3 bg-card flex items-center justify-end space-x-3 rounded-b-sm'>{children}</div>
);

export default DialogFooter;
