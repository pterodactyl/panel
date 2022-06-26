import React from 'react';
import { CheckIcon, ExclamationIcon, InformationCircleIcon, ShieldExclamationIcon } from '@heroicons/react/outline';
import classNames from 'classnames';

interface Props {
    type: 'danger' | 'info' | 'success' | 'warning';
    className?: string;
}

export default ({ type, className }: Props) => {
    const [Component, styles] = (function (): [(props: React.ComponentProps<'svg'>) => JSX.Element, string] {
        switch (type) {
            case 'danger':
                return [ShieldExclamationIcon, 'bg-red-500 text-red-50'];
            case 'warning':
                return [ExclamationIcon, 'bg-yellow-600 text-yellow-50'];
            case 'success':
                return [CheckIcon, 'bg-green-600 text-green-50'];
            case 'info':
                return [InformationCircleIcon, 'bg-primary-500 text-primary-50'];
        }
    })();

    return (
        <div className={classNames('flex items-center justify-center w-10 h-10 rounded-full', styles, className)}>
            <Component className={'w-6 h-6'} />
        </div>
    );
};
