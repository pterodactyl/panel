import React from 'react';
import { Checkbox as BaseCheckbox } from '@base-ui/react/checkbox';
import { Check, Minus } from 'lucide-react';
import { useSkin } from '@/themes/skins';

export type CheckboxProps = Omit<
    React.ComponentProps<typeof BaseCheckbox.Root>,
    'checked' | 'defaultChecked' | 'onCheckedChange' | 'className' | 'render' | 'children'
> & {
    checked: boolean;
    onChange: (checked: boolean) => void;
    className?: string;
};

export default function Checkbox({ checked, onChange, indeterminate, className, ...props }: CheckboxProps) {
    const ui = useSkin('Checkbox', {});

    return (
        <BaseCheckbox.Root
            {...props}
            checked={checked}
            indeterminate={indeterminate}
            onCheckedChange={onChange}
            {...ui('root', className)}
        >
            <BaseCheckbox.Indicator {...ui('indicator')}>
                {indeterminate ? (
                    <Minus size={14} strokeWidth={2.5} aria-hidden />
                ) : (
                    <Check size={14} strokeWidth={2.5} aria-hidden />
                )}
            </BaseCheckbox.Indicator>
        </BaseCheckbox.Root>
    );
}
