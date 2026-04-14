import * as React from 'react';
import classNames from 'classnames';

export type FlashMessageType = 'success' | 'info' | 'warning' | 'error';

interface Props {
    title?: string;
    children: string;
    type?: FlashMessageType;
}

const styling = (type?: FlashMessageType): string => {
    switch (type) {
        case 'error':
            return 'bg-red-600 border-red-800';
        case 'info':
            return 'bg-primary-600 border-primary-800';
        case 'success':
            return 'bg-green-600 border-green-800';
        case 'warning':
            return 'bg-yellow-600 border-yellow-800';
        default:
            return '';
    }
};

const getBackground = (type?: FlashMessageType): string => {
    switch (type) {
        case 'error':
            return 'bg-red-500';
        case 'info':
            return 'bg-primary-500';
        case 'success':
            return 'bg-green-500';
        case 'warning':
            return 'bg-yellow-500';
        default:
            return '';
    }
};

const MessageBox = ({ title, children, type }: Props) => (
    <div
        className={classNames(
            'p-2 border items-center leading-normal rounded flex w-full text-sm text-white lg:inline-flex',
            styling(type)
        )}
        role={'alert'}
    >
        {title && (
            <span
                className={classNames(
                    'title flex rounded-full uppercase px-2 py-1 text-xs font-bold mr-3 leading-none',
                    getBackground(type)
                )}
            >
                {title}
            </span>
        )}
        <span className="mr-2 text-left flex-auto">{children}</span>
    </div>
);
MessageBox.displayName = 'MessageBox';

export default MessageBox;
