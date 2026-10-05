import React from 'react';
import { Checkbox as BaseCheckbox } from '@base-ui/react/checkbox';
import { Check, Minus } from 'lucide-react';
import { cn } from '@/lib/cn';

export type CheckboxProps = Omit<
    React.ComponentProps<typeof BaseCheckbox.Root>,
    'checked' | 'defaultChecked' | 'onCheckedChange' | 'className' | 'render' | 'children'
> & {
    checked: boolean;
    onChange: (checked: boolean) => void;
    className?: string;
};

const rootClass = [
    'flex items-center justify-center shrink-0 w-4 h-4 rounded-xs cursor-pointer',
    'bg-input border border-input text-primary-foreground transition-colors duration-75 hover:border-primary/70',
    'data-[checked]:bg-primary data-[checked]:border-transparent',
    'data-[indeterminate]:bg-primary data-[indeterminate]:border-transparent',
    'data-[disabled]:opacity-60 data-[disabled]:cursor-default',
    '[&_svg]:size-3.5',
].join(' ');

export default function Checkbox({ checked, onChange, indeterminate, className, ...props }: CheckboxProps) {
    return (
        <BaseCheckbox.Root
            {...props}
            checked={checked}
            indeterminate={indeterminate}
            onCheckedChange={onChange}
            className={cn(rootClass, className)}
        >
            <BaseCheckbox.Indicator>
                {indeterminate ? (
                    <Minus size={14} strokeWidth={2.5} aria-hidden />
                ) : (
                    <Check size={14} strokeWidth={2.5} aria-hidden />
                )}
            </BaseCheckbox.Indicator>
        </BaseCheckbox.Root>
    );
}
