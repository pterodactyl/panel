import classic from '@/themes/classic';
import type { InspectedView } from '@/views/inspect';
import type { ViewLayer } from '@/views/registry';

/** The entry to add to a theme's `views` to start overriding a view. */
export const overrideSnippet = (name: string): string => `'${name}': ({ model, Default }) => <Default />,`;

/** Classic's own views have source to eject; the rest render the panel's own components. */
export const isEjectable = (name: string): boolean => Object.hasOwn(classic.views ?? {}, name);

export const ejectCommand = (name: string): string => `npx pterodactyl-theme eject ${name}`;

/** The layer that rendered: the top one that did not fail. */
export function renderingLayer(view: Pick<InspectedView, 'layers' | 'failed'>): ViewLayer | undefined {
    return [...view.layers].reverse().find((layer) => !view.failed.includes(layer.id));
}
