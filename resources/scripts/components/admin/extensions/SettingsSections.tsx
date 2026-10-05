import { useId, useRef, useState, type ReactNode } from 'react';
import type { AdminExtensionSettingField } from '@/api/admin/extensions/queries';
import Label from '@/components/elements/Label';
import Select from '@/components/ui/Select';
import { cn } from '@/lib/cn';

export const GENERAL_SECTION = 'General';

export const settingsBodyClass = 'flex h-[min(30rem,calc(100dvh-14rem))] flex-col';

export interface SettingsSection {
    name: string;
    fields: AdminExtensionSettingField[];
}

export function groupSettingsBySection(schema: AdminExtensionSettingField[]): SettingsSection[] {
    if (!schema.some((field) => field.tab)) {
        return [{ name: GENERAL_SECTION, fields: schema }];
    }

    const sections = new Map<string, AdminExtensionSettingField[]>();
    if (schema.some((field) => !field.tab)) {
        sections.set(GENERAL_SECTION, []);
    }

    for (const field of schema) {
        const name = field.tab || GENERAL_SECTION;
        sections.set(name, [...(sections.get(name) ?? []), field]);
    }

    return [...sections].map(([name, fields]) => ({ name, fields }));
}

export default function SettingsSections({
    schema,
    renderField,
}: {
    schema: AdminExtensionSettingField[];
    renderField: (field: AdminExtensionSettingField) => ReactNode;
}) {
    const id = useId();
    const scrollRef = useRef<HTMLDivElement>(null);
    const sections = groupSettingsBySection(schema);
    const [selected, setSelected] = useState(sections[0].name);
    const current = sections.find((section) => section.name === selected) ?? sections[0];

    return (
        <div className={settingsBodyClass}>
            {sections.length > 1 && (
                <div className={'shrink-0 pb-4 mb-1 border-b border-border'}>
                    <Label htmlFor={id}>Section</Label>
                    <Select
                        id={id}
                        value={current.name}
                        options={sections.map((section) => ({ value: section.name, label: section.name }))}
                        onChange={(value) => {
                            setSelected(String(value));
                            if (scrollRef.current) scrollRef.current.scrollTop = 0;
                        }}
                    />
                </div>
            )}
            <div
                ref={scrollRef}
                className={cn(
                    '-mx-1 min-h-0 flex-1 overflow-y-auto overscroll-contain px-1 pb-1',
                    sections.length > 1 && 'pt-3'
                )}
            >
                {sections.map((section) => (
                    <div key={section.name} hidden={section !== current} className={'space-y-5'}>
                        {section.fields.map(renderField)}
                    </div>
                ))}
            </div>
        </div>
    );
}
