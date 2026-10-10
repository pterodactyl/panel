import React, { useId, useState } from 'react';
import { Clipboard, Eye, EyeOff, RotateCcwKey } from 'lucide-react';
import { useFieldContext } from './context';
import Label from '@/components/elements/Label';
import { TextInput, TextArea, NumberInput } from './controls';
import { firstError } from './errors';
import { capitalize } from '@/lib/strings';
import Select, { type SelectOption } from '@/components/ui/Select';
import Switch, { type SwitchProps } from '@/components/ui/Switch';
import Checkbox from '@/components/ui/Checkbox';
import Button from '@/components/elements/Button';
import CopyOnClick from '@/components/elements/CopyOnClick';
import Icon from '@/components/elements/Icon';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import { cn } from '@/lib/cn';
import { generateSecurePassword } from '@/lib/passwords';

type BaseProps = {
    label?: string;
    description?: string;
    light?: boolean;
};

const HelpText = ({ id, error, description }: { id: string; error?: string; description?: string }) => {
    if (error) {
        return (
            <p id={id} className='input-help error'>
                {capitalize(error)}
            </p>
        );
    }

    if (!description) {
        return null;
    }

    return (
        <p id={id} className='input-help'>
            {description}
        </p>
    );
};

const describedBy = (...ids: (string | undefined)[]): string | undefined =>
    ids.filter((id) => !!id).join(' ') || undefined;

function useFieldState<TValue>(id: string | undefined, description: string | undefined) {
    const field = useFieldContext<TValue>();
    const generatedId = useId();
    const error = firstError(field.state.meta.errors);
    const helpId = `${generatedId}-help`;

    return {
        field,
        error,
        fieldId: id ?? generatedId,
        helpId,
        describedById: error || description ? helpId : undefined,
    };
}

export type TextFieldProps = BaseProps &
    Omit<React.InputHTMLAttributes<HTMLInputElement>, 'name' | 'value' | 'onChange' | 'defaultValue'>;

export function TextField({
    id,
    label,
    description,
    light,
    onBlur,
    'aria-describedby': ariaDescribedBy,
    'aria-invalid': ariaInvalid,
    ...props
}: TextFieldProps) {
    const { field, error, fieldId, helpId, describedById } = useFieldState<string>(id, description);

    return (
        <div>
            {label && (
                <Label htmlFor={fieldId} isLight={light}>
                    {label}
                </Label>
            )}
            <TextInput
                {...props}
                id={fieldId}
                value={field.state.value ?? ''}
                onValueChange={(value: string) => field.handleChange(value)}
                onBlur={(event) => {
                    field.handleBlur();
                    onBlur?.(event);
                }}
                aria-invalid={error ? true : ariaInvalid}
                aria-describedby={describedBy(ariaDescribedBy, describedById)}
                $hasError={!!error}
                $isLight={light}
            />
            <HelpText id={helpId} error={error} description={description} />
        </div>
    );
}

export type PasswordFieldProps = BaseProps &
    Omit<React.InputHTMLAttributes<HTMLInputElement>, 'name' | 'value' | 'onChange' | 'defaultValue' | 'type'>;

export function PasswordField({
    id,
    label,
    description,
    light,
    onBlur,
    className,
    'aria-describedby': ariaDescribedBy,
    'aria-invalid': ariaInvalid,
    ...props
}: PasswordFieldProps) {
    const { field, error, fieldId, helpId, describedById } = useFieldState<string>(id, description);
    const [visible, setVisible] = useState(false);
    const [generated, setGenerated] = useState(false);
    const toggleLabel = `${visible ? 'Hide' : 'Show'} ${label?.toLowerCase() ?? 'password'}`;

    const generate = () => {
        field.handleChange(generateSecurePassword());
        setGenerated(true);
    };

    return (
        <div>
            {label && (
                <Label htmlFor={fieldId} isLight={light}>
                    {label}
                </Label>
            )}
            <div className='relative'>
                <TextInput
                    {...props}
                    id={fieldId}
                    type={visible ? 'text' : 'password'}
                    value={field.state.value ?? ''}
                    onValueChange={(value: string) => {
                        field.handleChange(value);
                        setGenerated(false);
                    }}
                    onBlur={(event) => {
                        field.handleBlur();
                        onBlur?.(event);
                    }}
                    aria-invalid={error ? true : ariaInvalid}
                    aria-describedby={describedBy(ariaDescribedBy, describedById)}
                    $hasError={!!error}
                    $isLight={light}
                    className={cn('pr-24', className)}
                />
                <div className='absolute inset-y-0 right-2 flex items-center gap-1'>
                    <Tooltip content='Generate secure password'>
                        <span className='inline-flex'>
                            <Button.Text
                                type='button'
                                size='xsmall'
                                isSecondary
                                aria-label='Generate secure password'
                                onClick={generate}
                            >
                                <Icon icon={RotateCcwKey} className='h-3.5 w-3.5' />
                            </Button.Text>
                        </span>
                    </Tooltip>
                    <Tooltip content={toggleLabel}>
                        <span className='inline-flex'>
                            <Button.Text
                                type='button'
                                size='xsmall'
                                isSecondary
                                aria-label={toggleLabel}
                                aria-pressed={visible}
                                disabled={!field.state.value}
                                onClick={() => setVisible((current) => !current)}
                            >
                                <Icon icon={visible ? EyeOff : Eye} className='h-3.5 w-3.5' />
                            </Button.Text>
                        </span>
                    </Tooltip>
                    {generated && (
                        <Tooltip content='Copy password'>
                            <span className='inline-flex'>
                                <CopyOnClick text={field.state.value} showInNotification={false}>
                                    <Button.Text
                                        type='button'
                                        size='xsmall'
                                        isSecondary
                                        aria-label='Copy password'
                                    >
                                        <Icon icon={Clipboard} className='h-3.5 w-3.5' />
                                    </Button.Text>
                                </CopyOnClick>
                            </span>
                        </Tooltip>
                    )}
                </div>
            </div>
            <HelpText id={helpId} error={error} description={description} />
        </div>
    );
}

export type TextAreaFieldProps = BaseProps &
    Omit<React.TextareaHTMLAttributes<HTMLTextAreaElement>, 'name' | 'value' | 'onChange' | 'defaultValue'>;

export function TextAreaField({
    id,
    label,
    description,
    light,
    onBlur,
    'aria-describedby': ariaDescribedBy,
    'aria-invalid': ariaInvalid,
    ...props
}: TextAreaFieldProps) {
    const { field, error, fieldId, helpId, describedById } = useFieldState<string>(id, description);

    return (
        <div>
            {label && (
                <Label htmlFor={fieldId} isLight={light}>
                    {label}
                </Label>
            )}
            <TextArea
                {...props}
                id={fieldId}
                value={field.state.value ?? ''}
                onChange={(event) => field.handleChange(event.target.value)}
                onBlur={(event) => {
                    field.handleBlur();
                    onBlur?.(event);
                }}
                aria-invalid={error ? true : ariaInvalid}
                aria-describedby={describedBy(ariaDescribedBy, describedById)}
                $hasError={!!error}
                $isLight={light}
            />
            <HelpText id={helpId} error={error} description={description} />
        </div>
    );
}

export type NumberFieldProps = BaseProps & {
    id?: string;
    min?: number;
    max?: number;
    placeholder?: string;
    disabled?: boolean;
    format?: Intl.NumberFormatOptions;
};

/** Binds a `number | null` field; an emptied input stores null. */
export function NumberField({ id, label, description, light, min, max, format, ...props }: NumberFieldProps) {
    const { field, error, fieldId, helpId, describedById } = useFieldState<number | null>(id, description);

    return (
        <div>
            {label && (
                <Label htmlFor={fieldId} isLight={light}>
                    {label}
                </Label>
            )}
            <NumberInput
                {...props}
                id={fieldId}
                value={field.state.value ?? null}
                onValueChange={(value) => field.handleChange(value)}
                onBlur={field.handleBlur}
                min={min}
                max={max}
                format={format}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedById}
                $hasError={!!error}
                $isLight={light}
            />
            <HelpText id={helpId} error={error} description={description} />
        </div>
    );
}

export type { SelectOption };

export type SelectFieldProps = BaseProps & {
    id?: string;
    options: SelectOption[];
    disabled?: boolean;
    placeholder?: string;
    emptyMessage?: string;
    onChange?: (value: string | number) => void;
    onSearchChange?: (value: string) => void;
};

export function SelectField({
    id,
    label,
    description,
    light,
    options,
    disabled,
    placeholder,
    emptyMessage,
    onChange,
    onSearchChange,
}: SelectFieldProps) {
    const { field, error, fieldId, helpId, describedById } = useFieldState<string | number>(id, description);

    return (
        <div>
            {label && (
                <Label htmlFor={fieldId} isLight={light}>
                    {label}
                </Label>
            )}
            <Select
                id={fieldId}
                options={options}
                value={field.state.value ?? null}
                onChange={(value) => {
                    field.handleChange(value);
                    onChange?.(value);
                }}
                disabled={disabled}
                placeholder={placeholder}
                emptyMessage={emptyMessage}
                hasError={!!error}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedById}
                onBlur={field.handleBlur}
                onSearchChange={onSearchChange}
            />
            <HelpText id={helpId} error={error} description={description} />
        </div>
    );
}

export type MultiSelectFieldProps = BaseProps & {
    id?: string;
    options: SelectOption[];
    disabled?: boolean;
    placeholder?: string;
    searchPlaceholder?: string;
};

export function MultiSelectField({
    id,
    label,
    description,
    light,
    options,
    disabled,
    placeholder = 'None selected',
    searchPlaceholder,
}: MultiSelectFieldProps) {
    const { field, error, fieldId, helpId, describedById } = useFieldState<(string | number)[]>(id, description);
    const selected = Array.isArray(field.state.value) ? field.state.value : [];

    return (
        <div>
            {label && (
                <Label htmlFor={fieldId} isLight={light}>
                    {label}
                </Label>
            )}
            <Select
                multiple
                id={fieldId}
                options={options}
                value={selected}
                onChange={(value) => field.handleChange(value)}
                disabled={disabled}
                placeholder={placeholder}
                searchPlaceholder={searchPlaceholder}
                hasError={!!error}
                aria-invalid={error ? true : undefined}
                aria-describedby={describedById}
                onBlur={field.handleBlur}
            />
            <HelpText id={helpId} error={error} description={description} />
        </div>
    );
}

export type SwitchFieldProps = {
    label?: string;
    description?: string;
    disabled?: boolean;
    controlPosition?: SwitchProps['controlPosition'];
    className?: string;
};

export function SwitchField({ label, description, disabled, controlPosition, className }: SwitchFieldProps) {
    const field = useFieldContext<boolean>();

    return (
        <Switch
            checked={!!field.state.value}
            onChange={(checked) => field.handleChange(checked)}
            onBlur={field.handleBlur}
            disabled={disabled}
            label={label}
            description={description}
            controlPosition={controlPosition}
            className={className}
        />
    );
}

export type CheckboxFieldProps = {
    value: string;
    disabled?: boolean;
    className?: string;
};

/** Adds or removes `value` in a `string[]` field. */
export function CheckboxField({ value, disabled, className }: CheckboxFieldProps) {
    const field = useFieldContext<string[]>();
    const selected = Array.isArray(field.state.value) ? field.state.value : [];

    return (
        <Checkbox
            className={className}
            checked={selected.includes(value)}
            disabled={disabled}
            onChange={(checked) => {
                const set = new Set(selected);

                if (checked) {
                    set.add(value);
                } else {
                    set.delete(value);
                }

                field.handleChange([...set]);
            }}
        />
    );
}
