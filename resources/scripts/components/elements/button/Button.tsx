import classNames from 'classnames';
import styles from './style.module.css';
import React, { forwardRef } from 'react';
import { ButtonProps, Options } from '@/components/elements/button/types';

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
    ({ children, shape, size, variant, className, ...rest }, ref) => {
        return (
            <button
                ref={ref}
                className={classNames(
                    styles.button,
                    styles.primary,
                    {
                        [styles.secondary]: variant === Options.Variant.Secondary,
                        [styles.square]: shape === Options.Shape.IconSquare,
                        [styles.small]: size === Options.Size.Small,
                        [styles.large]: size === Options.Size.Large,
                    },
                    className
                )}
                {...rest}
            >
                {children}
            </button>
        );
    }
);

const TextButton = forwardRef<HTMLButtonElement, ButtonProps>(({ className, ...props }, ref) => (
    // @ts-expect-error not sure how to get this correct
    <Button ref={ref} className={classNames(styles.text, className)} {...props} />
));

const SuccessButton = forwardRef<HTMLButtonElement, ButtonProps>(({ className, ...props }, ref) => (
    // @ts-expect-error not sure how to get this correct
    <Button ref={ref} className={classNames(styles.success, className)} {...props} />
));

const WarnButton = forwardRef<HTMLButtonElement, ButtonProps>(({ className, ...props }, ref) => (
    // @ts-expect-error not sure how to get this correct
    <Button ref={ref} className={classNames(styles.warn, className)} {...props} />
));

const DangerButton = forwardRef<HTMLButtonElement, ButtonProps>(({ className, ...props }, ref) => (
    // @ts-expect-error not sure how to get this correct
    <Button ref={ref} className={classNames(styles.danger, className)} {...props} />
));

const _Button = Object.assign(Button, {
    Sizes: Options.Size,
    Shapes: Options.Shape,
    Variants: Options.Variant,
    Text: TextButton,
    Danger: DangerButton,
    Success: SuccessButton,
    Warn: WarnButton,
});

export default _Button;
