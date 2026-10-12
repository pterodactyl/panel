import type { AdminThemeSetting, AdminThemeSettingValue } from '@/api/admin/themes/queries';
import { isString } from '@/lib/objects';

type Writer = (value: string | null) => void;

const blank = (value: AdminThemeSettingValue | undefined): value is null | undefined | '' =>
    value === null || value === undefined || value === '';

/** What the panel writes for a setting's token (ThemeSetting::tokenValue on the server). */
export const tokenValue = (setting: AdminThemeSetting, value: AdminThemeSettingValue | undefined): string | null => {
    if (blank(value)) {
        return null;
    }

    switch (setting.type) {
        case 'toggle':
            return value === true ? '1' : '0';
        case 'number':
            return `${Number(value)}${setting.unit ?? ''}`;
        case 'text':
            return JSON.stringify(String(value));
        case 'image':
            return `url(${JSON.stringify(String(value))})`;
        case 'color':
        case 'select':
            return String(value);
    }
};

/** What the panel writes for a setting's `<html>` attribute. */
export const attributeValue = (value: AdminThemeSettingValue | undefined): string | null =>
    blank(value) ? null : String(value);

/**
 * Shows unsaved settings on the page itself: tokens as inline custom properties on `<html>`
 * and attributes on `<html>`, as the server renders them once saved. Whatever was there
 * before is remembered, so `restore` puts the page back.
 */
export function createLivePreview(root: HTMLElement = document.documentElement) {
    const original = new Map<string, { value: string | null; write: Writer }>();

    const write = (key: string, current: string | null, next: string | null, writer: Writer) => {
        if (!original.has(key)) {
            original.set(key, { value: current, write: writer });
        }

        writer(next);
    };

    return {
        apply(setting: AdminThemeSetting, value: AdminThemeSettingValue | undefined) {
            const effective = blank(value) ? setting.default : value;
            const { token, attribute } = setting;

            if (token) {
                write(token, root.style.getPropertyValue(token) || null, tokenValue(setting, effective), (next) =>
                    isString(next) ? root.style.setProperty(token, next) : root.style.removeProperty(token)
                );
            }

            if (attribute) {
                write(attribute, root.getAttribute(attribute), attributeValue(effective), (next) =>
                    isString(next) ? root.setAttribute(attribute, next) : root.removeAttribute(attribute)
                );
            }
        },
        restore() {
            for (const { value, write: writer } of original.values()) {
                writer(value);
            }

            original.clear();
        },
        /** The values were saved: they stay on the page, and what was there before is forgotten. */
        keep() {
            original.clear();
        },
    };
}

export type LivePreview = ReturnType<typeof createLivePreview>;
