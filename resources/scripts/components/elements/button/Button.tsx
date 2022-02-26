import React, { forwardRef } from 'react';
import classNames from 'classnames';
import styles from './button.module.css';

export type ButtonProps = JSX.IntrinsicElements['button'] & {
    square?: boolean;
    small?: boolean;
}

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
    ({ children, square, small, className, ...rest }, ref) => {
        return (
            <button
                ref={ref}
                className={classNames(styles.button, { [styles.square]: square, [styles.small]: small }, className)}
                {...rest}
            >
                {children}
            </button>
        );
    },
);

const TextButton = forwardRef<HTMLButtonElement, ButtonProps>(({ className, ...props }, ref) => (
    // @ts-expect-error
    <Button ref={ref} className={classNames(styles.text, className)} {...props} />
));

const DangerButton = forwardRef<HTMLButtonElement, ButtonProps>(({ className, ...props }, ref) => (
    // @ts-expect-error
    <Button ref={ref} className={classNames(styles.danger, className)} {...props} />
));

const _Button = Object.assign(Button, { Text: TextButton, Danger: DangerButton });

export default _Button;
