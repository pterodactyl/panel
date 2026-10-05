/** @vitest-environment jsdom */
import { cleanup, render, waitFor } from '@testing-library/react';
import { LifeBuoy, Puzzle, Star } from 'lucide-react';
import { afterEach, expect, it } from 'vitest';
import NamedIcon, { loadIconNames } from '@/components/elements/NamedIcon';
import { iconNames, resolveIcon } from '@/components/elements/iconCatalog';

afterEach(cleanup);

const svgClass = (container: HTMLElement) => container.querySelector('svg')?.getAttribute('class') ?? '';

it('renders bundled names immediately and holds the box of lazy names until the icon set arrives', async () => {
    const bundled = render(<NamedIcon name={'puzzle'} />);
    expect(svgClass(bundled.container)).toContain('lucide-puzzle');

    const lazy = render(<NamedIcon name={'life-buoy'} size={16} />);
    expect(lazy.container.querySelector('svg')).toBeNull();
    expect(lazy.container.querySelector('span')).toHaveStyle({ width: '16px', height: '16px' });
    await waitFor(() => expect(svgClass(lazy.container)).toContain('lucide-life-buoy'));
});

it('falls back to the default icon for names lucide does not have', async () => {
    const unknown = render(<NamedIcon name={'not-an-icon'} />);
    await waitFor(() => expect(svgClass(unknown.container)).toContain('lucide-puzzle'));

    const custom = render(<NamedIcon name={'chart'} fallback={Star} />);
    await waitFor(() => expect(svgClass(custom.container)).toContain('lucide-star'));
});

it('resolves every published icon name and nothing outside the list', async () => {
    expect(iconNames.length).toBeGreaterThan(1500);
    expect(iconNames.filter((name) => !resolveIcon(name))).toEqual([]);
    expect(resolveIcon('life-buoy')).toBe(LifeBuoy);
    expect(resolveIcon('puzzle')).toBe(Puzzle);
    for (const name of ['icon', 'icons', 'LifeBuoy', 'life-buoy-icon', 'lucide-life-buoy', 'constructor']) {
        expect(resolveIcon(name)).toBeUndefined();
    }
    await expect(loadIconNames()).resolves.toBe(iconNames);
});
