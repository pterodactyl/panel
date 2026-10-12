import { useMemo, useSyncExternalStore } from 'react';
import { ChevronRight, X } from 'lucide-react';
import { getViewProbe, type ViewProbe } from '@/views/inspect';
import { parentViews, viewElements } from './geometry';
import { modelJson } from './model';
import { CopyButton, LayerStack, ModelTree, OverrideHelp, Section, SlotList, TokenList } from './parts';
import { themeSettingTokens, tokensInUse } from './tokens';

interface PanelProps {
    element: Element;
    onPin: (element: Element) => void;
    onClose: () => void;
}

/** Views below this one, by name, with the first of each to pin. */
function nestedViews(wrapper: Element): { name: string; count: number; element: Element }[] {
    const found = new Map<string, { name: string; count: number; element: Element }>();

    for (const element of wrapper.querySelectorAll('[data-view]')) {
        const name = element.getAttribute('data-view') ?? '';
        const entry = found.get(name);

        found.set(name, entry ? { ...entry, count: entry.count + 1 } : { name, count: 1, element });
    }

    return [...found.values()];
}

function ViewPanel({ element, probe, onPin, onClose }: PanelProps & { probe: ViewProbe }) {
    const view = useSyncExternalStore(probe.subscribe, probe.current);
    const { definition } = view;
    const parents = useMemo(
        () =>
            parentViews(element)
                .map((parent, level) => ({ parent, level }))
                .reverse(),
        [element]
    );
    const nested = useMemo(() => nestedViews(element), [element]);
    const tokens = useMemo(() => tokensInUse(viewElements(element)), [element]);
    const settings = useMemo(() => themeSettingTokens(document), []);
    const hasInputs = Object.keys(view.inputs).length > 0;

    return (
        <aside
            aria-label='View inspector'
            className='pointer-events-auto fixed inset-y-0 right-0 flex w-[26rem] max-w-full flex-col border-l border-border bg-card text-card-foreground shadow-2xl'
        >
            <header className='border-b border-border px-4 py-3'>
                {parents.length > 0 && (
                    <nav aria-label='Enclosing views' className='mb-1 flex flex-wrap items-center gap-1 text-xs'>
                        {parents.map(({ parent, level }) => (
                            <span key={level} className='inline-flex items-center gap-1'>
                                <button
                                    type='button'
                                    className='font-mono text-muted-foreground hover:text-foreground'
                                    onClick={() => onPin(parent)}
                                >
                                    {parent.getAttribute('data-view')}
                                </button>
                                <ChevronRight className='h-3 w-3 text-muted-foreground' aria-hidden />
                            </span>
                        ))}
                    </nav>
                )}
                <div className='flex items-start gap-3'>
                    <div className='min-w-0 flex-1'>
                        <h2 className='truncate font-mono text-base font-semibold'>{definition.name}</h2>
                        <p className='text-xs text-muted-foreground'>
                            {definition.kind} · {definition.context} context
                            {definition.extensionId && ` · defined by ${definition.extensionId}`}
                        </p>
                    </div>
                    <button
                        type='button'
                        aria-label='Close the inspector panel'
                        className='rounded-sm p-1 text-muted-foreground hover:bg-popover hover:text-foreground'
                        onClick={onClose}
                    >
                        <X className='h-4 w-4' aria-hidden />
                    </button>
                </div>
                {!element.isConnected && (
                    <p className='mt-2 text-xs text-warning'>This view is no longer on the page.</p>
                )}
            </header>
            <div className='min-h-0 flex-1 overflow-y-auto'>
                <Section title='Override'>
                    <OverrideHelp name={definition.name} />
                </Section>
                <Section title='Layers' count={view.layers.length}>
                    <LayerStack view={view} />
                </Section>
                <Section title='Model'>
                    <div className='mb-2 flex justify-end'>
                        <CopyButton text={modelJson(view.model)} label='Copy JSON' />
                    </div>
                    <ModelTree value={view.model} />
                </Section>
                {hasInputs && (
                    <Section title='Inputs' open={false}>
                        <ModelTree value={view.inputs} />
                    </Section>
                )}
                <Section title='Slots' count={definition.slots?.length ?? 0}>
                    <SlotList slots={definition.slots ?? []} />
                </Section>
                {nested.length > 0 && (
                    <Section title='Nested views' count={nested.length}>
                        <ul className='space-y-1'>
                            {nested.map((entry) => (
                                <li key={entry.name}>
                                    <button
                                        type='button'
                                        className='font-mono text-xs text-accent hover:underline'
                                        onClick={() => onPin(entry.element)}
                                    >
                                        {entry.name}
                                    </button>
                                    {entry.count > 1 && (
                                        <span className='ml-1 text-xs text-muted-foreground'>×{entry.count}</span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </Section>
                )}
                <Section title='Tokens in use' count={tokens.length}>
                    <TokenList tokens={tokens} empty='No rule on this view reads a custom property.' />
                </Section>
                <Section title='Theme settings' open={false}>
                    <TokenList tokens={settings.tokens} empty='The active theme sets no tokens.' />
                    <div className='mt-3'>
                        <TokenList tokens={settings.attributes} empty='No attributes on the document.' />
                    </div>
                </Section>
            </div>
        </aside>
    );
}

/** The pinned view's name, layers, model, slots and tokens, with the snippet to override it. */
export function InspectorPanel(props: PanelProps) {
    const probe = getViewProbe(props.element);

    return probe ? <ViewPanel {...props} probe={probe} /> : null;
}
