import { afterEach, expect, it } from 'vitest';
import dayjs, { setDateLocale } from '@/lib/dayjs';

const date = new Date(2026, 9, 3, 20, 2, 5);

afterEach(async () => {
    await setDateLocale('en');
});

it('formats dates in the language of the panel', async () => {
    expect(await setDateLocale('de')).toBe('de');
    expect(dayjs(date).format('LL')).toBe('3. Oktober 2026');
    expect(dayjs(date).format('LTS')).toBe('20:02:05');
});

it('uses the regional variant of a language when dayjs has one', async () => {
    expect(await setDateLocale('pt-BR')).toBe('pt-br');
    expect(await setDateLocale('en_AU')).toBe('en-au');
    expect(dayjs(date).format('L')).toBe('03/10/2026');
});

it('falls back to the language, then english', async () => {
    expect(await setDateLocale('de-XX')).toBe('de');
    expect(await setDateLocale('xx')).toBe('en');
    expect(dayjs(date).format('ll')).toBe('Oct 3, 2026');
});
