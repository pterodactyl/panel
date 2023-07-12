import classNames from 'classnames';
import type { ComponentProps } from 'react';
import { forwardRef } from 'react';

import styles from './styles.module.css';

type Props = Omit<ComponentProps<'input'>, 'type'> & {
    indeterminate?: boolean;
};

export default forwardRef<HTMLInputElement, Props>(({ className, indeterminate, ...props }, ref) => (
    <input
        ref={ref}
        type={'checkbox'}
        className={classNames(
            'form-checkbox',
            {
                [styles.checkbox]: true,
                [styles.indeterminate]: indeterminate,
            },
            className,
        )}
        {...props}
    />
));
