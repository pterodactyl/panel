import { useId, useState, type ChangeEvent } from 'react';
import { Clipboard, Eye, EyeOff, RotateCcwKey } from 'lucide-react';
import { Button, CopyOnClick, Icon, Input, Label, TextArea, Tooltip, type FieldModel } from '@/sdk';
import { generateSecurePassword } from '@/lib/passwords';

interface FieldProps {
    field: FieldModel;
    label?: string;
    description?: string;
    light?: boolean;
    id?: string;
    className?: string;
}

function HelpText({ id, error, description }: { id: string; error: string | null; description?: string }) {
    if (error) {
        return (
            <p id={id} className='input-help error'>
                {error}
            </p>
        );
    }

    return description ? (
        <p id={id} className='input-help'>
            {description}
        </p>
    ) : null;
}

function useFieldIds(field: FieldModel, id: string | undefined, description: string | undefined) {
    const generated = useId();
    const helpId = `${generated}-help`;

    return { fieldId: id ?? generated, helpId, describedBy: field.error || description ? helpId : undefined };
}

/** Label, input and help text for an engine field, whose own props pass through untouched. */
export function TextField({
    field,
    label,
    description,
    light,
    id,
    className,
    title,
    autoFocus,
}: FieldProps & { title?: string; autoFocus?: boolean }) {
    const { fieldId, helpId, describedBy } = useFieldIds(field, id, description);

    return (
        <div>
            {label && (
                <Label htmlFor={fieldId} isLight={light}>
                    {label}
                </Label>
            )}
            <Input
                {...field.fieldProps}
                id={fieldId}
                className={className}
                title={title}
                autoFocus={autoFocus}
                aria-invalid={field.error ? true : undefined}
                aria-describedby={describedBy}
                $hasError={!!field.error}
                $isLight={light}
            />
            <HelpText id={helpId} error={field.error} description={description} />
        </div>
    );
}

export function PasswordField({ field, label, description, light, id, className }: FieldProps) {
    const { fieldId, helpId, describedBy } = useFieldIds(field, id, description);
    const [visible, setVisible] = useState(false);
    const [generated, setGenerated] = useState(false);
    const toggleLabel = (visible ? 'Hide' : 'Show') + ' ' + (label?.toLowerCase() ?? 'password');

    const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
        field.fieldProps.onChange(event);
        setGenerated(false);
    };

    const generate = () => {
        const value = generateSecurePassword();

        field.fieldProps.onChange({ currentTarget: { value } } as ChangeEvent<HTMLInputElement>);
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
                <Input
                    {...field.fieldProps}
                    id={fieldId}
                    type={visible ? 'text' : 'password'}
                    onChange={handleChange}
                    className={['pr-24', className].filter(Boolean).join(' ')}
                    aria-invalid={field.error ? true : undefined}
                    aria-describedby={describedBy}
                    $hasError={!!field.error}
                    $isLight={light}
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
                                disabled={!field.fieldProps.value}
                                onClick={() => setVisible((current) => !current)}
                            >
                                <Icon icon={visible ? EyeOff : Eye} className='h-3.5 w-3.5' />
                            </Button.Text>
                        </span>
                    </Tooltip>
                    {generated && (
                        <Tooltip content='Copy password'>
                            <span className='inline-flex'>
                                <CopyOnClick text={field.fieldProps.value} showInNotification={false}>
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
            <HelpText id={helpId} error={field.error} description={description} />
        </div>
    );
}

export function TextAreaField({
    field,
    label,
    description,
    light,
    id,
    className,
    rows,
}: FieldProps & { rows?: number }) {
    const { fieldId, helpId, describedBy } = useFieldIds(field, id, description);

    return (
        <div>
            {label && (
                <Label htmlFor={fieldId} isLight={light}>
                    {label}
                </Label>
            )}
            <TextArea
                {...field.fieldProps}
                id={fieldId}
                className={className}
                rows={rows}
                aria-invalid={field.error ? true : undefined}
                aria-describedby={describedBy}
                $hasError={!!field.error}
                $isLight={light}
            />
            <HelpText id={helpId} error={field.error} description={description} />
        </div>
    );
}
