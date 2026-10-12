import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { Check, Copy } from 'lucide-react';
import copyToClipboard from '@/lib/clipboard';
import { cn } from '@/lib/cn';
import type { InspectedView } from '@/views/inspect';
import { describeModel, type ModelNode, type ModelTone } from './model';
import { ejectCommand, isEjectable, overrideSnippet, renderingLayer } from './snippets';
import type { CssToken } from './tokens';

export function Section({
    title,
    count,
    open = true,
    children,
}: {
    title: string;
    count?: number;
    open?: boolean;
    children?: ReactNode;
}) {
    return (
        <details open={open} className='group border-b border-border'>
            <summary className='flex cursor-pointer select-none items-center gap-2 px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground hover:text-foreground'>
                <span className='transition-transform group-open:rotate-90'>›</span>
                {title}
                {count !== undefined && <span className='font-normal normal-case tracking-normal'>{count}</span>}
            </summary>
            <div className='px-4 pb-3'>{children}</div>
        </details>
    );
}

export function CopyButton({ text, label, className }: { text: string; label: string; className?: string }) {
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        if (!copied) {
            return;
        }

        const timer = setTimeout(() => setCopied(false), 1500);

        return () => clearTimeout(timer);
    }, [copied]);

    return (
        <button
            type='button'
            className={cn(
                'inline-flex shrink-0 items-center gap-1.5 rounded-sm border border-border bg-popover px-2 py-1 text-xs text-foreground transition-colors hover:bg-secondary',
                className
            )}
            onClick={() => void copyToClipboard(text).then(setCopied)}
        >
            {copied ? (
                <Check className='h-3.5 w-3.5 text-success' aria-hidden />
            ) : (
                <Copy className='h-3.5 w-3.5' aria-hidden />
            )}
            {copied ? 'Copied' : label}
        </button>
    );
}

const tones = {
    string: 'text-success',
    number: 'text-accent',
    boolean: 'text-warning',
    empty: 'text-muted-foreground',
    function: 'text-muted-foreground italic',
    other: 'text-foreground',
} satisfies Record<ModelTone, string>;

function ModelBranch({ label, node, depth }: { label: string | null; node: ModelNode; depth: number }) {
    const key = label !== null && <span className='text-foreground/80'>{label}: </span>;

    if (node.kind === 'value') {
        return (
            <div className='truncate py-px' title={node.text}>
                {key}
                <span className={tones[node.tone]}>{node.text}</span>
            </div>
        );
    }

    return (
        <details open={depth < 2} className='group/branch'>
            <summary className='cursor-pointer select-none py-px marker:text-muted-foreground'>
                {key}
                <span className='text-muted-foreground'>{node.summary}</span>
            </summary>
            <div className='ml-2 border-l border-border pl-3'>
                {node.entries.map((entry) => (
                    <ModelBranch key={entry.key} label={entry.key} node={entry.node} depth={depth + 1} />
                ))}
            </div>
        </details>
    );
}

/** A model or inputs as a collapsible tree. */
export function ModelTree({ value }: { value: unknown }) {
    const node = useMemo(() => describeModel(value), [value]);

    return (
        <div className='font-mono text-xs leading-5'>
            <ModelBranch label={null} node={node} depth={0} />
        </div>
    );
}

const sources = { classic: 'Classic', theme: 'Theme', extension: 'Extension' };

/** The layer chain top first, marking the layer that rendered and any that failed. */
export function LayerStack({ view }: { view: Pick<InspectedView, 'layers' | 'failed'> }) {
    const rendered = renderingLayer(view);

    if (!view.layers.length) {
        return <p className='text-sm text-muted-foreground'>No layer implements this view.</p>;
    }

    return (
        <ol className='space-y-1.5'>
            {[...view.layers].reverse().map((layer) => {
                const failed = view.failed.includes(layer.id);

                return (
                    <li
                        key={`${layer.source}:${layer.id}`}
                        className={cn(
                            'flex items-center gap-2 rounded-sm border px-2.5 py-1.5 text-sm',
                            layer === rendered ? 'border-primary bg-primary/10' : 'border-border',
                            failed && 'border-destructive/50 bg-destructive/10'
                        )}
                    >
                        <span className='font-mono'>{layer.id}</span>
                        <span className='text-xs text-muted-foreground'>{sources[layer.source]}</span>
                        <span className='ml-auto text-xs'>
                            {layer === rendered && <span className='text-primary'>renders</span>}
                            {failed && <span className='text-destructive'>failed, fell back</span>}
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}

export function SlotList({ slots }: { slots: readonly string[] }) {
    if (!slots.length) {
        return <p className='text-sm text-muted-foreground'>This view renders no slots.</p>;
    }

    return (
        <ul className='space-y-1 font-mono text-xs'>
            {slots.map((slot) => (
                <li key={slot}>{slot}</li>
            ))}
        </ul>
    );
}

export function TokenList({ tokens, empty }: { tokens: readonly CssToken[]; empty: string }) {
    if (!tokens.length) {
        return <p className='text-sm text-muted-foreground'>{empty}</p>;
    }

    return (
        <dl className='grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)] gap-x-3 gap-y-1 font-mono text-xs'>
            {tokens.map((token) => (
                <div key={token.name} className='contents'>
                    <dt className='truncate text-foreground/80' title={token.name}>
                        {token.name}
                    </dt>
                    <dd className='flex min-w-0 items-center gap-1.5'>
                        {token.value && CSS.supports('color', token.value) && (
                            <span
                                aria-hidden
                                className='h-3 w-3 shrink-0 rounded-xs border border-border'
                                style={{ background: token.value }}
                            />
                        )}
                        <span className='truncate' title={token.value}>
                            {token.value || '(unset)'}
                        </span>
                    </dd>
                </div>
            ))}
        </dl>
    );
}

/** A line of code to paste somewhere, with its copy button. */
export function CodeLine({ code, label = 'Copy' }: { code: string; label?: string }) {
    return (
        <div className='flex items-start gap-2'>
            <code className='min-w-0 flex-1 rounded-sm bg-sunken px-2 py-1.5 font-mono text-xs break-words whitespace-pre-wrap'>
                {code}
            </code>
            <CopyButton text={code} label={label} />
        </div>
    );
}

/** The snippet to start overriding a view, and the command that ejects classic's source for it. */
export function OverrideHelp({ name }: { name: string }) {
    return (
        <div className='space-y-2'>
            <CodeLine code={overrideSnippet(name)} label='Copy override' />
            {isEjectable(name) ? (
                <CodeLine code={ejectCommand(name)} />
            ) : (
                <p className='text-xs text-muted-foreground'>
                    Classic has no source of its own to eject here yet; wrap <code className='font-mono'>Default</code>{' '}
                    instead.
                </p>
            )}
        </div>
    );
}
