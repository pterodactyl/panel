import React, { useState } from 'react';
import { ArrowDown, ArrowUp, Plus, X } from 'lucide-react';
import type { AdminExtensionSettingField, AdminExtensionSettingValue } from '@/api/admin/extensions/queries';
import Button from '@/components/elements/Button';
import Icon from '@/components/elements/Icon';
import Label from '@/components/elements/Label';
import Select from '@/components/ui/Select';
import Switch from '@/components/ui/Switch';
import { FileInput, NumberInput, TextArea, TextInput } from '@/components/form/controls';
import { isString } from '@/lib/objects';

export type SettingValue = AdminExtensionSettingValue | undefined;

export type SettingFileActions = {
    pending: boolean;
    onUpload: (file: File) => void;
    onClear: () => void;
};

type ControlProps = {
    field: AdminExtensionSettingField;
    value: SettingValue;
    onChange: (value: AdminExtensionSettingValue) => void;
};

const uploadedFilePrefix = '/extension-files/';

// <input type="color"> only accepts #rrggbb.
const pickerFallback = `#${'0'.repeat(6)}`;

const hexColor = /^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i;

const asText = (value: SettingValue): string => (isString(value) ? value : '');

const asItems = (value: SettingValue): string[] => (Array.isArray(value) ? value.filter(isString) : []);

const asChoices = (value: SettingValue): (string | number)[] => (Array.isArray(value) ? value : []);

const asNumber = (value: SettingValue): number | null =>
    value === undefined || value === null || value === '' ? null : Number(value);

// Row keys continue past the largest existing key so a reset or append never reuses a mounted row.
const freshKeys = (existing: number[], count: number): number[] => {
    const start = existing.length > 0 ? Math.max(...existing) + 1 : 0;

    return Array.from({ length: count }, (_, i) => start + i);
};

export const pickerColor = (value: string): string | null => {
    if (!hexColor.test(value)) {
        return null;
    }

    const digits = value.slice(1);
    const expanded =
        digits.length <= 4 ? Array.from(digits.slice(0, 3), (digit) => digit + digit).join('') : digits.slice(0, 6);

    return `#${expanded.toLowerCase()}`;
};

export const submittedItems = (value: SettingValue): string[] =>
    asItems(value)
        .map((item) => item.trim())
        .filter((item) => item !== '');

function ColorControl({ field, value, onChange }: ControlProps) {
    const color = asText(value);

    return (
        <div className='flex items-center gap-2'>
            <label
                className='relative block h-10 w-10 shrink-0 overflow-hidden rounded-sm border border-border bg-background'
                style={{ backgroundColor: color || undefined }}
            >
                <input
                    type='color'
                    aria-label={`${field.label} picker`}
                    className='absolute inset-0 h-full w-full cursor-pointer opacity-0'
                    value={pickerColor(color) ?? pickerFallback}
                    onChange={(event) => onChange(event.currentTarget.value)}
                />
            </label>
            <TextInput
                aria-label={field.label}
                value={color}
                spellCheck={false}
                placeholder='Hex or oklch() colour'
                onChange={(event: React.ChangeEvent<HTMLInputElement>) => onChange(event.currentTarget.value)}
            />
        </div>
    );
}

function TextAreaControl({ field, value, onChange }: ControlProps) {
    const text = asText(value);
    const maxLength = field.constraints?.max_length ?? undefined;

    return (
        <>
            <TextArea
                aria-label={field.label}
                rows={5}
                value={text}
                maxLength={maxLength}
                onChange={(event) => onChange(event.currentTarget.value)}
            />
            {maxLength !== undefined && (
                <p className='mt-1 text-right text-xs text-muted-foreground'>
                    {text.length} / {maxLength}
                </p>
            )}
        </>
    );
}

function RowButton({
    label,
    icon,
    disabled,
    onClick,
}: {
    label: string;
    icon: React.ComponentProps<typeof Icon>['icon'];
    disabled?: boolean;
    onClick: () => void;
}) {
    return (
        <Button
            type='button'
            aria-label={label}
            title={label}
            color='grey'
            isSecondary
            disabled={disabled}
            className='inline-flex h-9 w-9 shrink-0 items-center justify-center p-0'
            onClick={onClick}
        >
            <Icon icon={icon} />
        </Button>
    );
}

function ListControl({ field, value, onChange }: ControlProps) {
    const items = asItems(value);
    const maxItems = field.constraints?.max_items ?? undefined;
    const [keys, setKeys] = useState<number[]>([]);
    const rowKeys = keys.length === items.length ? keys : freshKeys(keys, items.length);

    if (rowKeys !== keys) {
        setKeys(rowKeys);
    }

    const swap = <T,>(list: T[], index: number, offset: -1 | 1): T[] => {
        const next = [...list];

        [next[index], next[index + offset]] = [next[index + offset], next[index]];

        return next;
    };

    const replace = (index: number, item: string) =>
        onChange(items.map((current, i) => (i === index ? item : current)));
    const remove = (index: number) => {
        setKeys(rowKeys.filter((_key, i) => i !== index));
        onChange(items.filter((_item, i) => i !== index));
    };

    const move = (index: number, offset: -1 | 1) => {
        setKeys(swap(rowKeys, index, offset));
        onChange(swap(items, index, offset));
    };

    const add = () => {
        setKeys([...rowKeys, ...freshKeys(rowKeys, 1)]);
        onChange([...items, '']);
    };

    return (
        <div className='space-y-2'>
            {items.map((item, index) => (
                <div key={rowKeys[index]} className='flex items-center gap-2'>
                    <TextInput
                        aria-label={`${field.label} item ${index + 1}`}
                        value={item}
                        onChange={(event: React.ChangeEvent<HTMLInputElement>) =>
                            replace(index, event.currentTarget.value)
                        }
                    />
                    <RowButton
                        label={`Move item ${index + 1} up`}
                        icon={ArrowUp}
                        disabled={index === 0}
                        onClick={() => move(index, -1)}
                    />
                    <RowButton
                        label={`Move item ${index + 1} down`}
                        icon={ArrowDown}
                        disabled={index === items.length - 1}
                        onClick={() => move(index, 1)}
                    />
                    <RowButton label={`Remove item ${index + 1}`} icon={X} onClick={() => remove(index)} />
                </div>
            ))}
            <Button
                type='button'
                size='xsmall'
                isSecondary
                disabled={maxItems !== undefined && items.length >= maxItems}
                onClick={add}
            >
                <span className='inline-flex items-center gap-1.5'>
                    <Icon icon={Plus} />
                    Add item
                </span>
            </Button>
            {maxItems !== undefined && (
                <span className='ml-3 text-xs text-muted-foreground'>
                    {items.length} / {maxItems}
                </span>
            )}
        </div>
    );
}

function FileControl({ field, file }: { field: AdminExtensionSettingField; file?: SettingFileActions }) {
    const url = asText(field.value);
    const accept = field.constraints?.accept ?? [];
    const maxKilobytes = field.constraints?.max_kilobytes;
    const isImage = accept.every((type) => type.startsWith('image/'));
    const limits = [accept.length > 0 ? accept.join(', ') : null, maxKilobytes ? `up to ${maxKilobytes} KB` : null]
        .filter(Boolean)
        .join(' · ');

    return (
        <div className='space-y-2'>
            {url !== '' && (
                <div className='flex items-center gap-3'>
                    {isImage && (
                        <img
                            src={url}
                            alt=''
                            className='h-12 w-12 shrink-0 rounded-sm border border-border bg-background object-contain'
                        />
                    )}
                    <a
                        href={url}
                        target='_blank'
                        rel='noreferrer'
                        className='min-w-0 flex-1 truncate font-mono text-xs text-muted-foreground hover:underline'
                    >
                        {url}
                    </a>
                    {url.startsWith(uploadedFilePrefix) && (
                        <Button
                            type='button'
                            size='xsmall'
                            color='red'
                            isSecondary
                            disabled={!file || file.pending}
                            onClick={() => file?.onClear()}
                        >
                            Remove
                        </Button>
                    )}
                </div>
            )}
            <FileInput
                aria-label={field.label}
                accept={accept.join(',')}
                disabled={!file || file.pending}
                onChange={(event) => {
                    const selected = event.currentTarget.files?.[0];

                    // Lets the same file be chosen again.
                    event.currentTarget.value = '';
                    if (selected) {
                        file?.onUpload(selected);
                    }
                }}
            />
            <p className='text-xs text-muted-foreground'>
                {limits !== '' && `${limits}. `}Uploads are stored as soon as they are chosen.
            </p>
        </div>
    );
}

function Control({ field, value, onChange, file }: ControlProps & { file?: SettingFileActions }) {
    switch (field.field) {
        case 'select':
            return (
                <Select
                    value={(value as string | number | null | undefined) ?? null}
                    onChange={onChange}
                    options={field.options.map((option) => ({
                        value: option.value as string | number,
                        label: option.label,
                    }))}
                />
            );
        case 'multiselect':
            return (
                <Select
                    multiple
                    value={asChoices(value)}
                    onChange={onChange}
                    options={field.options.map((option) => ({
                        value: option.value as string | number,
                        label: option.label,
                    }))}
                />
            );
        case 'number':
            return <NumberInput value={asNumber(value)} onValueChange={(next) => onChange(next ?? '')} />;
        case 'password':
            return (
                <TextInput
                    type='password'
                    value={asText(value)}
                    placeholder={
                        field.value ? `Stored: ${String(field.value)} - enter a new value to replace it` : undefined
                    }
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => onChange(event.currentTarget.value)}
                />
            );
        case 'color':
            return <ColorControl field={field} value={value} onChange={onChange} />;
        case 'textarea':
            return <TextAreaControl field={field} value={value} onChange={onChange} />;
        case 'list':
            return <ListControl field={field} value={value} onChange={onChange} />;
        case 'file':
            return <FileControl field={field} file={file} />;
        case 'text':
        case 'toggle':
            return (
                <TextInput
                    value={asText(value)}
                    onChange={(event: React.ChangeEvent<HTMLInputElement>) => onChange(event.currentTarget.value)}
                />
            );
    }
}

export default function SettingField({
    field,
    value,
    onChange,
    file,
}: ControlProps & {
    file?: SettingFileActions;
}) {
    if (field.field === 'toggle') {
        return (
            <Switch checked={!!value} onChange={onChange} label={field.label} description={field.help ?? undefined} />
        );
    }

    return (
        <div>
            <Label as='div' className='flex flex-wrap items-center gap-2'>
                {field.label}
                {field.visibility === 'public' && (
                    <span
                        className='rounded-sm bg-accent/10 px-1.5 py-0.5 text-xs font-medium text-accent'
                        title='This value is sent to every visitor, including those who are not signed in.'
                    >
                        Visible to guests
                    </span>
                )}
            </Label>
            <Control field={field} value={value} onChange={onChange} file={file} />
            {field.help && <p className='mt-1 text-xs text-muted-foreground'>{field.help}</p>}
        </div>
    );
}
