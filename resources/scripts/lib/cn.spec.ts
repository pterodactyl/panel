import { describe, expect, it } from 'vitest';
import { cn, registerClassPrefixes } from '@/lib/cn';

describe('cn', () => {
    it('keeps the last of two conflicting panel utilities', () => {
        expect(cn('px-2 py-1', false, 'px-4')).toBe('py-1 px-4');
    });

    it('resolves a prefix that is registered after its classes were first merged', () => {
        expect(cn('mt-2 px-4', 'late:mt-4')).toBe('mt-2 px-4 late:mt-4');
        registerClassPrefixes(['late']);

        expect(cn('mt-2 px-4', 'late:mt-4')).toBe('px-4 late:mt-4');
    });

    describe('with the prefixes of two enabled extensions', () => {
        registerClassPrefixes(['hw', null, undefined, 'bk']);

        it('keeps extension utilities, their variants and modifiers intact', () => {
            const classes = 'hw:flex hw:lg:hidden bk:hover:bg-accent/80 bk:-mt-2 hw:w-[13px] hw:mt-4! bk:group';

            expect(cn(classes)).toBe(classes);
        });

        it('lets either extension replace the panel default it conflicts with', () => {
            expect(cn('mt-2 px-4', 'hw:mt-4')).toBe('px-4 hw:mt-4');
            expect(cn('px-4 py-2', 'bk:p-0')).toBe('bk:p-0');
            expect(cn('rounded-md text-sm text-foreground', 'bk:text-muted-foreground')).toBe(
                'rounded-md text-sm bk:text-muted-foreground'
            );
        });

        it('resolves conflicts per variant, as it does for panel utilities', () => {
            expect(cn('mt-2 lg:mt-0', 'hw:lg:mt-4')).toBe('mt-2 hw:lg:mt-4');
            expect(cn('bg-primary hover:bg-primary/90', 'bk:bg-accent')).toBe('hover:bg-primary/90 bk:bg-accent');
        });

        it('resolves conflicts within and between extensions', () => {
            expect(cn('hw:gap-2', 'hw:gap-3')).toBe('hw:gap-3');
            expect(cn('hw:gap-2', 'bk:gap-3')).toBe('bk:gap-3');
        });

        it('leaves classes that are not Tailwind utilities alone', () => {
            expect(cn('flex', 'hw:my-widget my-widget')).toBe('flex hw:my-widget my-widget');
        });

        it('leaves the classes of a prefix no extension registered beside the panel default', () => {
            expect(cn('mt-2', 'ext:mt-4')).toBe('mt-2 ext:mt-4');
        });

        it('ignores names Tailwind would not accept as a prefix', () => {
            registerClassPrefixes(['', 'a-b', 'UP']);

            expect(cn('mt-2', 'a-b:mt-4 UP:mt-4')).toBe('mt-2 a-b:mt-4 UP:mt-4');
        });
    });
});
