import React from 'react';
import { Input as BaseInput } from '@base-ui/react/input';
import { NumberField } from '@base-ui/react/number-field';
import { useSkin } from '@/themes/skins';

interface ThemeProps {
    $isLight?: boolean;
    $hasError?: boolean;
}

const controlState = ({ $isLight, $hasError }: ThemeProps) => ({ light: !!$isLight, error: !!$hasError });

type WithStringClassName<TProps> = Omit<TProps, 'className'> & { className?: string };

export type TextInputProps = ThemeProps & WithStringClassName<React.ComponentProps<typeof BaseInput>>;
export type TextAreaProps = ThemeProps & React.ComponentProps<'textarea'>;
export type NumberInputProps = ThemeProps &
    Pick<
        React.ComponentProps<typeof NumberField.Root>,
        'value' | 'onValueChange' | 'min' | 'max' | 'step' | 'format' | 'disabled'
    > &
    WithStringClassName<Omit<React.ComponentProps<typeof NumberField.Input>, 'value' | 'onChange' | 'disabled'>>;
export type FileInputProps = Omit<React.ComponentProps<'input'>, 'type'>;

export const TextInput = ({ $isLight, $hasError, className, ...props }: TextInputProps) => {
    const ui = useSkin('Input', controlState({ $isLight, $hasError }));

    return <BaseInput {...ui('root', className)} {...props} />;
};

export const FileInput = ({ className, ...props }: FileInputProps) => {
    const ui = useSkin('FileInput', {});

    return <input {...props} type='file' {...ui('root', className)} />;
};

export const TextArea = ({ $isLight, $hasError, className, ...props }: TextAreaProps) => {
    const ui = useSkin('TextArea', controlState({ $isLight, $hasError }));

    return <textarea {...ui('root', className)} {...props} />;
};

export const NumberInput = ({
    $isLight,
    $hasError,
    className,
    value,
    onValueChange,
    min,
    max,
    step,
    format,
    disabled,
    ...props
}: NumberInputProps) => {
    const ui = useSkin('NumberInput', controlState({ $isLight, $hasError }));

    return (
        <NumberField.Root
            {...ui('root')}
            value={value}
            onValueChange={onValueChange}
            min={min}
            max={max}
            step={step}
            format={{ useGrouping: false, ...format }}
            disabled={disabled}
        >
            <NumberField.Input {...ui('input', className)} {...props} />
        </NumberField.Root>
    );
};
