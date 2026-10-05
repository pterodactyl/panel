// @vitest-environment jsdom
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { notifyThemeChange, onThemeChange, readThemeToken, syncThemeColorMeta } from './theme';

// MutationObserver callbacks are microtasks and notifications are coalesced into a frame.
const settle = async () => {
    await Promise.resolve();
    await vi.advanceTimersByTimeAsync(20);
};

const addStyle = (css: string) => {
    const style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);

    return style;
};

describe('theme change notifications', () => {
    beforeEach(() => {
        vi.useFakeTimers({ toFake: ['requestAnimationFrame', 'cancelAnimationFrame', 'setTimeout', 'clearTimeout'] });
    });

    afterEach(() => {
        vi.useRealTimers();
        document.head.innerHTML = '';
        document.documentElement.removeAttribute('style');
        document.documentElement.removeAttribute('data-sub-navigation');
    });

    it('reports stylesheets added to, edited in and removed from the head', async () => {
        const listener = vi.fn();
        const stop = onThemeChange(listener);

        const style = addStyle(':root { --accent: red; }');
        await settle();
        expect(listener).toHaveBeenCalledTimes(1);

        style.textContent = ':root { --accent: blue; }';
        await settle();
        expect(listener).toHaveBeenCalledTimes(2);

        style.remove();
        await settle();
        expect(listener).toHaveBeenCalledTimes(3);

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        document.head.appendChild(link);
        await settle();
        link.dispatchEvent(new Event('load'));
        await settle();
        expect(listener).toHaveBeenCalledTimes(5);
        stop();
    });

    it('reports inline tokens and attributes on the root element', async () => {
        const listener = vi.fn();
        const stop = onThemeChange(listener);

        document.documentElement.style.setProperty('--primary', 'red');
        await settle();
        document.documentElement.dataset.subNavigation = 'side';
        await settle();

        expect(listener).toHaveBeenCalledTimes(2);
        stop();
    });

    it('coalesces a burst into one notification and ignores unrelated head changes', async () => {
        const listener = vi.fn();
        const stop = onThemeChange(listener);

        document.title = 'Console';
        const meta = document.createElement('meta');
        meta.name = 'description';
        document.head.appendChild(meta);
        meta.content = 'changed';
        document.head.appendChild(document.createElement('script'));
        await settle();
        expect(listener).not.toHaveBeenCalled();

        addStyle(':root { --card: red; }');
        addStyle(':root { --border: red; }');
        document.documentElement.style.setProperty('--primary', 'red');
        await settle();
        expect(listener).toHaveBeenCalledTimes(1);
        stop();
    });

    it('can be nudged by hand and stops after the last listener leaves', async () => {
        const listener = vi.fn();
        const stop = onThemeChange(listener);

        notifyThemeChange();
        await settle();
        expect(listener).toHaveBeenCalledTimes(1);

        stop();
        notifyThemeChange();
        addStyle(':root { --card: red; }');
        await settle();
        expect(listener).toHaveBeenCalledTimes(1);
    });
});

describe('theme-color meta', () => {
    beforeEach(() => {
        vi.useFakeTimers({ toFake: ['requestAnimationFrame', 'cancelAnimationFrame', 'setTimeout', 'clearTimeout'] });
        const meta = document.createElement('meta');
        meta.name = 'theme-color';
        meta.content = '#0e4688';
        meta.dataset.themeToken = '';
        document.head.appendChild(meta);
    });

    afterEach(() => {
        vi.useRealTimers();
        document.head.innerHTML = '';
    });

    const content = () => document.querySelector<HTMLMetaElement>('meta[name="theme-color"]')?.content;

    it('leaves the shipped value alone until a theme defines the token', () => {
        const stop = syncThemeColorMeta();

        expect(readThemeToken('--theme-color')).toBe('');
        expect(content()).toBe('#0e4688');
        stop();
    });

    it('follows the token as themes come and go', async () => {
        addStyle(':root { --theme-color: #101216; }');
        const stop = syncThemeColorMeta();
        expect(content()).toBe('#101216');

        addStyle(':root { --theme-color: #ffffff; }');
        await settle();
        expect(content()).toBe('#ffffff');
        stop();
    });

    it('leaves a theme-color registered by an extension alone', () => {
        document.head.innerHTML = '<meta name="theme-color" content="#0f172a">';
        addStyle(':root { --theme-color: #101216; }');
        const stop = syncThemeColorMeta();

        expect(content()).toBe('#0f172a');
        stop();
    });
});
