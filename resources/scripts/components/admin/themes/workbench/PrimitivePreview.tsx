import { useState } from 'react';
import type { WorkbenchWidth } from '@/router/search';
import type { UiPrimitive } from '@/themes/gallery';
import { CodeLine, ModelTree, Section } from '@/views/inspector/parts';
import type { ThemeScheme } from '@/views/theme';
import FixtureBoundary from './FixtureBoundary';
import PreviewFrame from './PreviewFrame';
import { frameSize, useWidth } from './stage';

export interface PrimitivePreviewProps {
    name: string;
    primitive: UiPrimitive;
    scheme: ThemeScheme;
    width: WorkbenchWidth;
}

/** Every sample of a UI kit primitive in one frame, with the props each renders with. */
export default function PrimitivePreview({ name, primitive, scheme, width }: PrimitivePreviewProps) {
    const [stage, setStage] = useState<HTMLElement | null>(null);
    const available = useWidth(stage);
    const size = frameSize(width, available);
    const samples = Object.entries(primitive.samples);

    return (
        <div className='flex min-w-0 flex-1 flex-col xl:flex-row'>
            <section className='min-w-0 flex-1 p-4 lg:p-6'>
                <header className='mb-4 flex flex-wrap items-baseline gap-x-3'>
                    <h2 className='font-mono text-lg font-semibold text-foreground'>{name}</h2>
                    <span className='text-xs text-muted-foreground'>UI kit primitive · {samples.length} samples</span>
                </header>
                <div ref={setStage}>
                    {available > 0 && (
                        <div className='rounded-sm ring-1 ring-border'>
                            <PreviewFrame label={name} width={size.width} scale={size.scale} scheme={scheme}>
                                <div className='grid grid-cols-[repeat(auto-fill,minmax(16rem,1fr))] gap-6 p-6'>
                                    {samples.map(([variant, sample]) => (
                                        <div key={variant} className='min-w-0'>
                                            <p className='mb-2 font-mono text-xs text-muted-foreground'>{variant}</p>
                                            <FixtureBoundary>{sample}</FixtureBoundary>
                                        </div>
                                    ))}
                                </div>
                            </PreviewFrame>
                        </div>
                    )}
                </div>
            </section>
            <aside
                aria-label='Primitive details'
                className='w-full shrink-0 border-t border-border bg-card xl:w-96 xl:border-t-0 xl:border-l'
            >
                <Section title='Skin'>
                    <div className='space-y-2'>
                        <CodeLine code={`${name}: skin('${name}', { /* parts */ }),`} />
                        <CodeLine code={`npx pterodactyl-theme skins ${name}`} />
                        <p className='text-xs text-muted-foreground'>
                            The command prints classic&apos;s skin for {name} to start from.
                        </p>
                    </div>
                </Section>
                <Section title='Sample props'>
                    <div className='space-y-3'>
                        {samples.map(([variant, sample]) => (
                            <div key={variant}>
                                <p className='mb-1 font-mono text-xs text-foreground'>{variant}</p>
                                <ModelTree value={sample.props} />
                            </div>
                        ))}
                    </div>
                </Section>
            </aside>
        </div>
    );
}
