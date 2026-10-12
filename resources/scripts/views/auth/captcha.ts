import { createElement, useCallback, useMemo, useRef, type ComponentType, type RefObject } from 'react';
import { toast } from 'sonner';
import { useSiteSettings } from '@/api/settings/queries';
import InvisibleRecaptcha, { type InvisibleRecaptchaHandle } from '@/components/elements/InvisibleRecaptcha';

function captchaLeaf(ref: RefObject<InvisibleRecaptchaHandle | null>, siteKey: string): ComponentType {
    return function Captcha() {
        return createElement(InvisibleRecaptcha, { ref, siteKey });
    };
}

/** The invisible reCAPTCHA of an auth form, when the panel enables it. */
interface AuthCaptcha {
    readonly Captcha: ComponentType | null;
    /** The value to send, or null once the user has been told the verification failed. */
    readonly token: () => Promise<string | null>;
}

export function useCaptcha(): AuthCaptcha {
    const ref = useRef<InvisibleRecaptchaHandle>(null);
    const { enabled, siteKey } = useSiteSettings().recaptcha;
    const Captcha = useMemo(() => (enabled ? captchaLeaf(ref, siteKey || '') : null), [enabled, siteKey]);
    const token = useCallback(async () => {
        if (!enabled) {
            return '';
        }

        const value = (await ref.current?.execute()) ?? '';

        if (!value) {
            toast.error('Captcha verification failed, please try again.');

            return null;
        }

        return value;
    }, [enabled]);

    return { Captcha, token };
}
