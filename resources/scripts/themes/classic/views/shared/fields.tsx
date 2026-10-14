import { useId } from 'react';
import { Input, Label, TextArea, type FieldModel } from '@/sdk';

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
