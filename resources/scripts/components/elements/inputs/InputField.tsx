import React, { forwardRef } from 'react';
import classNames from 'classnames';
import styles from './styles.module.css';

enum Variant {
    Normal,
    Snug,
    Loose,
}

const Component = forwardRef<HTMLInputElement, React.ComponentProps<'input'> & { variant?: Variant }>(
    ({ className, variant, ...props }, ref) => (
        <input
            ref={ref}
            className={classNames(
                'form-input',
                styles.text_input,
                { [styles.loose]: variant === Variant.Loose },
                className
            )}
            {...props}
        />
    )
);

const InputField = Object.assign(Component, { Variants: Variant });

export default InputField;
