import React, { useId } from 'react';
import { Switch as BaseSwitch } from '@base-ui/react/switch';
import Label from '@/components/elements/Label';
import { useSkin } from '@/themes/skins';

export interface SwitchProps {
    checked: boolean;
    onChange: (checked: boolean) => void;
    disabled?: boolean;
    id?: string;
    name?: string;
    label?: string;
    description?: string;
    controlPosition?: 'start' | 'end';
    className?: string;
    onBlur?: React.ComponentProps<typeof BaseSwitch.Root>['onBlur'];
    'aria-label'?: string;
    'aria-labelledby'?: string;
}

export default function Switch({
    checked,
    onChange,
    disabled,
    id,
    name,
    label,
    description,
    controlPosition = 'start',
    className,
    onBlur,
    'aria-label': ariaLabel,
    'aria-labelledby': ariaLabelledBy,
}: SwitchProps) {
    const generatedId = useId();
    const fieldId = id ?? generatedId;
    const labelId = `${generatedId}-label`;
    const descriptionId = `${generatedId}-description`;
    const hasText = !!label || !!description;
    const ui = useSkin('Switch', { described: !!description, position: controlPosition });
    const control = (
        <BaseSwitch.Root
            id={fieldId}
            name={name}
            checked={checked}
            onCheckedChange={onChange}
            onBlur={onBlur}
            disabled={disabled}
            aria-label={ariaLabel}
            aria-labelledby={ariaLabelledBy ?? (label ? labelId : undefined)}
            aria-describedby={description ? descriptionId : undefined}
            {...ui('control')}
        >
            <BaseSwitch.Thumb {...ui('thumb')} />
        </BaseSwitch.Root>
    );
    const text = hasText ? (
        <div {...ui('text')}>
            {label && (
                <Label id={labelId} htmlFor={fieldId} className={ui.classes('label')}>
                    {label}
                </Label>
            )}
            {description && (
                <p id={descriptionId} {...ui('description')}>
                    {description}
                </p>
            )}
        </div>
    ) : null;

    return (
        <div {...ui('root', className)}>
            {controlPosition === 'end' ? (
                <>
                    {text}
                    {control}
                </>
            ) : (
                <>
                    {control}
                    {text}
                </>
            )}
        </div>
    );
}
