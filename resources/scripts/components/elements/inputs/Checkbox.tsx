import React, { forwardRef } from 'react';
import styles from './inputs.module.css';
import classNames from 'classnames';

type Props = Omit<React.ComponentProps<'input'>, 'type'>

export default forwardRef<HTMLInputElement, Props>(({ className, ...props }, ref) => (
    <input
        ref={ref}
        type={'checkbox'}
        className={classNames('form-checkbox', styles.checkbox, className)} {...props}
    />
));
