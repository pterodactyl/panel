/** @vitest-environment jsdom */

import type React from 'react';
import { createRef } from 'react';
import { act, cleanup, render } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import InvisibleRecaptcha, { type InvisibleRecaptchaHandle } from './InvisibleRecaptcha';

type BadgeProps = { onChange?: (token: string) => void; onError?: () => void; onExpired?: () => void };

const recaptcha = vi.hoisted(() => {
    const badge: BadgeProps = {};

    return {
        instance: {
            execute: vi.fn<() => Promise<void> | undefined>(),
            reset: vi.fn<() => void>(),
            getResponse: vi.fn<() => string>(),
        },
        ready: true,
        badge,
    };
});

vi.mock('@google-recaptcha/react', () => ({
    GoogleReCaptchaProvider: ({ children }: { children: React.ReactNode }) => children,
    GoogleReCaptchaBadge: (props: BadgeProps) => {
        recaptcha.badge = props;

        return null;
    },
    useGoogleReCaptcha: () =>
        recaptcha.ready ? { instance: recaptcha.instance, isLoading: false } : { instance: undefined, isLoading: true },
}));

const renderRecaptcha = () => {
    const ref = createRef<InvisibleRecaptchaHandle>();

    render(<InvisibleRecaptcha ref={ref} siteKey='site-key' />);

    return ref;
};

const challengePopup = () => {
    const popup = document.createElement('div');

    popup.style.visibility = 'hidden';
    popup.innerHTML =
        '<div></div><div><iframe src="https://www.google.com/recaptcha/api2/bframe?k=site-key"></iframe></div>';
    document.body.append(popup);

    return popup;
};

afterEach(() => {
    cleanup();
    vi.useRealTimers();
    document.body.innerHTML = '';
});
beforeEach(() => {
    recaptcha.ready = true;
    recaptcha.instance.execute.mockReset();
    recaptcha.instance.reset.mockReset();
    recaptcha.instance.getResponse.mockReset().mockReturnValue('');
});

describe('InvisibleRecaptcha', () => {
    it('resolves with the token and resets the widget', async () => {
        const ref = renderRecaptcha();

        const token = ref.current!.execute();

        expect(recaptcha.instance.execute).toHaveBeenCalledOnce();
        act(() => recaptcha.badge.onChange?.('token-1'));

        await expect(token).resolves.toBe('token-1');
        expect(recaptcha.instance.reset).toHaveBeenCalledOnce();
    });

    it('resolves null while the script is not ready', async () => {
        recaptcha.ready = false;
        const ref = renderRecaptcha();

        await expect(ref.current!.execute()).resolves.toBeNull();
        expect(recaptcha.instance.execute).not.toHaveBeenCalled();
    });

    it('resolves null when executing throws or rejects', async () => {
        const ref = renderRecaptcha();

        recaptcha.instance.execute.mockImplementationOnce(() => {
            throw new Error('Google ReCaptcha has not been loaded');
        });
        await expect(ref.current!.execute()).resolves.toBeNull();

        recaptcha.instance.execute.mockRejectedValueOnce(new Error('No reCAPTCHA clients exist'));
        await expect(ref.current!.execute()).resolves.toBeNull();
        expect(recaptcha.instance.reset).toHaveBeenCalledTimes(2);
    });

    it('resolves null on error and expiry', async () => {
        const ref = renderRecaptcha();

        const errored = ref.current!.execute();

        act(() => recaptcha.badge.onError?.());
        await expect(errored).resolves.toBeNull();

        const expired = ref.current!.execute();

        act(() => recaptcha.badge.onExpired?.());
        await expect(expired).resolves.toBeNull();
    });

    it('settles a previous execution when a new one starts', async () => {
        const ref = renderRecaptcha();

        const first = ref.current!.execute();
        const second = ref.current!.execute();

        act(() => recaptcha.badge.onChange?.('token-2'));

        await expect(first).resolves.toBeNull();
        await expect(second).resolves.toBe('token-2');
    });

    it('resolves null when the challenge never completes', async () => {
        vi.useFakeTimers();
        const ref = renderRecaptcha();

        const token = ref.current!.execute();

        await act(async () => vi.advanceTimersByTimeAsync(120_000));

        await expect(token).resolves.toBeNull();
        expect(recaptcha.instance.reset).toHaveBeenCalledOnce();
    });

    it('resolves null when the image challenge is dismissed', async () => {
        vi.useFakeTimers();
        const popup = challengePopup();
        const ref = renderRecaptcha();

        const token = ref.current!.execute();

        popup.style.visibility = 'visible';
        await act(async () => vi.advanceTimersByTimeAsync(0));
        popup.style.visibility = 'hidden';
        await act(async () => vi.advanceTimersByTimeAsync(1_000));

        await expect(token).resolves.toBeNull();
    });

    it('uses the widget response when the challenge closes after verifying', async () => {
        vi.useFakeTimers();
        const popup = challengePopup();
        const ref = renderRecaptcha();

        const token = ref.current!.execute();

        popup.style.visibility = 'visible';
        await act(async () => vi.advanceTimersByTimeAsync(0));
        recaptcha.instance.getResponse.mockReturnValue('token-3');
        popup.style.visibility = 'hidden';
        await act(async () => vi.advanceTimersByTimeAsync(1_000));

        await expect(token).resolves.toBe('token-3');
    });
});
