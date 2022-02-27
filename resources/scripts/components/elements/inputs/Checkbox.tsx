import React, { forwardRef } from 'react';
import styles from './inputs.module.css';
import classNames from 'classnames';

type Props = Omit<React.ComponentProps<'input'>, 'type'> & {
    indeterminate?: boolean;
}

export default forwardRef<HTMLInputElement, Props>(({ className, indeterminate, ...props }, ref) => (
    <input
        ref={ref}
        type={'checkbox'}
        className={classNames('form-checkbox', {
            [styles.checkbox]: true,
            [styles.indeterminate]: indeterminate,
        }, className)}
        {...props}
    />
));
