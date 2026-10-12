/** @vitest-environment jsdom */
import { act, cleanup, render } from '@testing-library/react';
import type { ComponentType } from 'react';
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import Slot from '@/extensions/Slot';
import type { SlotName } from '@/extensions/registry';
import { clearThemeLayers, registerViewDefinition, registerViewLayer, type ViewProps } from './registry';
import { EngineSlots, SLOT_CHECK_DELAY_MS } from './slotCheck';
import { View } from './View';

const before = 'dashboard.before' satisfies SlotName;
const after = 'dashboard.after' satisfies SlotName;

type Props = ViewProps<'test.slotted'>;

beforeAll(() => {
    registerViewDefinition({
        name: 'test.slotted',
        kind: 'block',
        context: 'app',
        use: () => ({}),
        slots: [before, after],
    });
    registerViewLayer('classic', 'classic', {
        'test.slotted': () => (
            <>
                <Slot name={before} />
                <p>classic</p>
                <Slot name={after} />
            </>
        ),
    });
});

let warn: ReturnType<typeof vi.spyOn>;

beforeEach(() => {
    vi.useFakeTimers();
    warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
});

afterEach(() => {
    cleanup();
    clearThemeLayers();
    vi.useRealTimers();
    vi.restoreAllMocks();
});

async function renderWith(layer: ComponentType<Props>, engine: readonly string[] = []) {
    registerViewLayer('aurora', 'theme', { 'test.slotted': layer });
    render(
        <EngineSlots.Provider value={engine}>
            <View name='test.slotted' />
        </EngineSlots.Provider>
    );
    await act(async () => {
        vi.advanceTimersByTime(SLOT_CHECK_DELAY_MS);
    });
}

describe('slot check', () => {
    it('warns when a layer renders none of its view slots', async () => {
        await renderWith(() => <p>aurora</p>);

        expect(warn).toHaveBeenCalledWith(
            `[views] The "aurora" layer of "test.slotted" renders none of its slots (${before}, ${after}). ` +
                'Render them with <Slot name="…"/>, or render <Default/>, so extensions keep their place.'
        );
    });

    it('stays quiet for a layer that renders a slot or the layer below', async () => {
        await renderWith(() => <Slot name={after} />);
        cleanup();
        await renderWith(({ Default }) => (
            <section>
                <Default />
            </section>
        ));

        expect(warn).not.toHaveBeenCalled();
    });

    it('leaves out the slots the engine renders around a page', async () => {
        await renderWith(() => <p>aurora</p>, [before, after]);

        expect(warn).not.toHaveBeenCalled();
    });

    it('does not count the slots of a nested view', async () => {
        registerViewDefinition({ name: 'test.nested', kind: 'block', context: 'app', use: () => ({}) });
        registerViewLayer('classic', 'classic', { 'test.nested': () => <Slot name={before} /> });
        await renderWith(() => <View name='test.nested' />);

        expect(warn).toHaveBeenCalledOnce();
    });
});
