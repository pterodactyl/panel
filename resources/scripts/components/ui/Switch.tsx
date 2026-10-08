import React, { useId } from 'react';
import { Switch as BaseSwitch } from '@base-ui/react/switch';
import { cn } from '@/lib/cn';
import Label from '@/components/elements/Label';

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

const rootClass = [
    'group relative inline-block shrink-0 w-12 h-6 rounded-full border cursor-pointer select-none p-0',
    'bg-input border-input transition-colors duration-75',
    'hover:border-primary/70 active:bg-popover data-[checked]:bg-primary data-[checked]:border-primary',
    'data-[disabled]:opacity-60 data-[disabled]:cursor-default',
].join(' ');
const thumbClass = [
    'block absolute top-1/2 left-0.5 size-4.5 -translate-y-1/2 rounded-full border bg-background',
    'transition-[left] duration-75 ease-in',
    'group-data-[checked]:left-[calc(100%_-_1.125rem_-_0.125rem)]',
    'group-data-[checked]:bg-primary-foreground group-data-[checked]:border-primary-foreground',
].join(' ');

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
            className={rootClass}
        >
            <BaseSwitch.Thumb className={thumbClass} />
        </BaseSwitch.Root>
    );
    const text = hasText ? (
        <div className='min-w-0 flex-1'>
            {label && (
                <Label id={labelId} htmlFor={fieldId} className='cursor-pointer mb-0'>
                    {label}
                </Label>
            )}
            {description && (
                <p id={descriptionId} className='input-help'>
                    {description}
                </p>
            )}
        </div>
    ) : null;

    return (
        <div className={cn('flex gap-4', description ? 'items-start' : 'items-center', className)}>
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
