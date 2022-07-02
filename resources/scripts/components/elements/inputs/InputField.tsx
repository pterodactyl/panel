import React, { forwardRef } from 'react';
import classNames from 'classnames';
import styles from './styles.module.css';

export default forwardRef<HTMLInputElement, React.ComponentProps<'input'>>(({ className, ...props }, ref) => (
    <input ref={ref} className={classNames('form-input', styles.text_input, className)} {...props} />
));
