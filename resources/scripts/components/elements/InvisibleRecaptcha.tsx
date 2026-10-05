import React, { useCallback, useEffect, useImperativeHandle, useRef } from 'react';
import { GoogleReCaptchaProvider, GoogleReCaptchaBadge, useGoogleReCaptcha } from '@google-recaptcha/react';

export interface InvisibleRecaptchaHandle {
    // Resolves with the token, or null when none was obtained; never rejects.
    execute: () => Promise<string | null>;
}

type Resolver = (token: string | null) => void;

type RecaptchaInstance = NonNullable<ReturnType<typeof useGoogleReCaptcha>['instance']>;

interface PendingExecution {
    resolve: Resolver;
    finish: () => void;
}

// Upper bound for a single execution; matches the lifetime of a reCAPTCHA image challenge.
const EXECUTE_TIMEOUT_MS = 120_000;
// After the challenge popup closes, a successful verification can still deliver its token.
const DISMISS_GRACE_MS = 1_000;
const CHALLENGE_FRAME_SELECTOR = 'iframe[src*="/recaptcha/api2/bframe"], iframe[src*="/recaptcha/enterprise/bframe"]';

const readResponse = (instance: RecaptchaInstance): string | null => {
    try {
        return instance.getResponse() || null;
    } catch {
        return null;
    }
};

const resetWidget = (instance: RecaptchaInstance) => {
    try {
        instance.reset();
    } catch {
        // The widget has already been removed.
    }
};

/** Calls `onDismiss` once the reCAPTCHA challenge popup hides after being shown. */
function watchChallengeDismissal(onDismiss: () => void): () => void {
    let shown = false;
    let graceTimeout: number | undefined;

    const check = () => {
        const frame = document.querySelector<HTMLIFrameElement>(CHALLENGE_FRAME_SELECTOR);
        const popup = frame?.parentElement?.parentElement;
        if (!popup) {
            return;
        }

        if (popup.style.visibility === 'visible') {
            shown = true;
            window.clearTimeout(graceTimeout);
            graceTimeout = undefined;
        } else if (shown && graceTimeout === undefined) {
            graceTimeout = window.setTimeout(onDismiss, DISMISS_GRACE_MS);
        }
    };

    const observer = new MutationObserver(check);
    observer.observe(document.body, { subtree: true, childList: true, attributes: true, attributeFilter: ['style'] });

    return () => {
        observer.disconnect();
        window.clearTimeout(graceTimeout);
    };
}

// The badge rebuilds its widget whenever its callback identities change, so they must be stable.
interface RecaptchaBridgeProps {
    ref?: React.Ref<InvisibleRecaptchaHandle>;
}

const RecaptchaBridge = ({ ref }: RecaptchaBridgeProps) => {
    const { instance, isLoading } = useGoogleReCaptcha();
    const pendingRef = useRef<PendingExecution | null>(null);

    const settle = useCallback((token: string | null) => {
        const pending = pendingRef.current;
        pendingRef.current = null;
        pending?.finish();
        pending?.resolve(token);
    }, []);

    const onChange = useCallback((token: string) => settle(token || null), [settle]);
    const onExpired = useCallback(() => settle(null), [settle]);
    const onError = useCallback(() => settle(null), [settle]);

    useEffect(() => () => settle(null), [settle]);

    useImperativeHandle(
        ref,
        () => ({
            execute: () =>
                new Promise<string | null>((resolve) => {
                    settle(null);

                    if (isLoading || !instance) {
                        resolve(null);
                        return;
                    }

                    const timeout = window.setTimeout(() => settle(null), EXECUTE_TIMEOUT_MS);
                    const stopWatching = watchChallengeDismissal(() => settle(readResponse(instance)));
                    pendingRef.current = {
                        resolve,
                        finish: () => {
                            window.clearTimeout(timeout);
                            stopWatching();
                            resetWidget(instance);
                        },
                    };

                    try {
                        void Promise.resolve(instance.execute()).catch(() => settle(null));
                    } catch {
                        settle(null);
                    }
                }),
        }),
        [instance, isLoading, settle]
    );

    return <GoogleReCaptchaBadge onChange={onChange} onError={onError} onExpired={onExpired} />;
};
RecaptchaBridge.displayName = 'RecaptchaBridge';

interface Props {
    siteKey: string;
    ref?: React.Ref<InvisibleRecaptchaHandle>;
}

/** Invisible reCAPTCHA v2; call `ref.execute()` to obtain a token. */
const InvisibleRecaptcha = ({ siteKey, ref }: Props) => {
    return (
        <GoogleReCaptchaProvider type={'v2-invisible'} siteKey={siteKey || '_invalid_key'}>
            <RecaptchaBridge ref={ref} />
        </GoogleReCaptchaProvider>
    );
};
InvisibleRecaptcha.displayName = 'InvisibleRecaptcha';

export default InvisibleRecaptcha;
