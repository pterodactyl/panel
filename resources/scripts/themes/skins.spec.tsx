/** @vitest-environment jsdom */
import { act, cleanup, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Avatar from '@/components/Avatar';
import Button, { type ButtonProps } from '@/components/elements/Button';
import Badge, { type BadgeProps } from '@/components/elements/Badge';
import Card from '@/components/elements/Card';
import Checkbox from '@/components/ui/Checkbox';
import { Empty, EmptyMedia } from '@/components/ui/empty';
import { classicSkins } from '@/themes/classic/skins';
import { clearThemeSkins, getSkin, registerSkins, skin, UI_PRIMITIVE_PARTS, type ThemeSkins } from './skins';

afterEach(() => {
    cleanup();
    clearThemeSkins();
    vi.restoreAllMocks();
});

describe('skin layers', () => {
    it('replaces a part recipe wholesale and keeps the parts a layer leaves out', () => {
        registerSkins('aurora', { Button: { root: 'rounded-full px-6' } });
        render(<Button isLoading>Save</Button>);

        const button = screen.getByRole('button', { name: 'Save' });

        expect(button).toHaveAttribute('class', 'rounded-full px-6');
        expect(button.querySelector('[data-part="spinner"]')).toHaveClass('absolute', 'justify-center');
    });

    it('lets a function recipe build on the layers below, beside plain recipes of the same skin', () => {
        registerSkins('parent', { Button: { root: ({ color }) => `rounded-full bg-${color}` } });
        registerSkins('aurora', {
            Button: skin('Button', {
                root: (state, below) => below('shadow-xl'),
                // `extra` merges: its gap replaces classic's.
                label: (state, below) => below('gap-1 tracking-tight'),
                spinner: 'absolute inset-0',
            }),
        });

        render(
            <Button color='green' isLoading>
                Save
            </Button>
        );

        const button = screen.getByRole('button', { name: 'Save' });

        expect(button).toHaveAttribute('class', 'rounded-full bg-green shadow-xl');
        // `below` reads the state: classic hides a loading label.
        expect(screen.getByText('Save')).toHaveAttribute(
            'class',
            'inline-flex items-center text-transparent gap-1 tracking-tight'
        );
        expect(button.querySelector('[data-part="spinner"]')).toHaveAttribute('class', 'absolute inset-0');
    });

    it('stacks the extensions of every layer, in order', () => {
        registerSkins('parent', { Card: { root: (state, below) => below('rounded-lg') } });
        registerSkins('aurora', { Card: { root: (state, below) => below('ring-1') } });

        expect(getSkin('Card', { titled: true, actions: false, loading: false }).classes('root')).toBe(
            'bg-card in-data-[ui=dialog]:ring-1 in-data-[ui=dialog]:ring-border shadow-md rounded-lg ring-1'
        );
    });

    it('stacks classic first, then the chain parent first, and moves a re-registered layer up', () => {
        registerSkins('parent', { Badge: { root: 'parent' } });
        registerSkins('aurora', { Badge: { root: 'aurora' } });

        expect(getSkin('Badge', { tone: 'neutral', size: 'md', variant: 'solid' }).classes('root')).toBe('aurora');

        registerSkins('parent', { Badge: { root: 'parent' } });
        registerSkins('classic', classicSkins);

        expect(getSkin('Badge', { tone: 'neutral', size: 'md', variant: 'solid' }).classes('root')).toBe('parent');

        clearThemeSkins();

        expect(getSkin('Badge', { tone: 'danger', size: 'md', variant: 'solid' }).classes('root')).toBe(
            'rounded-sm text-xs px-2 py-1 bg-destructive text-destructive-foreground'
        );
    });

    it('re-renders primitives when the layers change', () => {
        render(<Button>Save</Button>);

        act(() => registerSkins('aurora', { Button: { root: 'rounded-full' } }));

        expect(screen.getByRole('button', { name: 'Save' })).toHaveAttribute('class', 'rounded-full');
    });

    it('resolves cva recipes with defaults, compound arrays and class or className', () => {
        registerSkins('aurora', {
            Badge: {
                root: {
                    base: 'badge',
                    variants: { tone: { danger: 'red', neutral: 'grey' }, size: { sm: 'small' } },
                    compoundVariants: [
                        { tone: ['danger', 'warning'], variant: 'soft', class: 'tinted' },
                        { size: 'md', className: 'roomy' },
                    ],
                    defaultVariants: { size: 'sm' },
                },
            },
        });

        expect(getSkin('Badge', { tone: 'danger', size: 'md', variant: 'soft' }).classes('root')).toBe(
            'badge red tinted roomy'
        );
        expect(getSkin('Badge', { tone: 'neutral', size: 'sm', variant: 'solid' }).classes('root', 'ml-2')).toBe(
            'badge grey small ml-2'
        );
    });

    it('ignores a failing recipe and a part the primitive does not have', () => {
        const error = vi.spyOn(console, 'error').mockImplementation(() => {});

        registerSkins('aurora', {
            Badge: {
                root: () => {
                    throw new Error('broken');
                },
                // @ts-expect-error -- Badge has no icon part.
                icon: 'h-4',
            },
        });
        render(<Badge>New</Badge>);

        expect(screen.getByText('New')).not.toHaveAttribute('class');
        expect(error).toHaveBeenCalledWith('[skins] The "aurora" recipe of Badge failed.', expect.any(Error));
    });
});

describe('primitive attributes', () => {
    it('marks the primitive, each part and the root state', () => {
        render(
            <Button color='red' size='xsmall' isSecondary isLoading>
                Delete
            </Button>
        );

        const button = screen.getByRole('button', { name: 'Delete' });

        expect(button).toHaveAttribute('data-ui', 'button');
        expect(button).toHaveAttribute('data-part', 'root');
        expect(button).toHaveAttribute('data-color', 'red');
        expect(button).toHaveAttribute('data-size', 'xsmall');
        expect(button).toHaveAttribute('data-secondary', '');
        expect(button).toHaveAttribute('data-loading', '');
        expect(screen.getByText('Delete')).toHaveAttribute('data-part', 'label');
        expect(screen.getByText('Delete')).not.toHaveAttribute('data-color');
    });

    it('skins an uploaded avatar picture like the generated pattern', () => {
        registerSkins('aurora', { Avatar: { root: 'ring-2' } });
        render(
            <>
                <Avatar name='alex' title />
                <Avatar name='sam' image='/sam.png' title />
            </>
        );

        for (const avatar of [screen.getByRole('img', { name: 'alex' }), screen.getByRole('img', { name: 'sam' })]) {
            expect(avatar).toHaveAttribute('data-ui', 'avatar');
            expect(avatar).toHaveAttribute('data-variant', 'beam');
            expect(avatar).toHaveClass('ring-2');
        }
    });

    it('leaves false state off and renders a part state on its own part', () => {
        render(
            <Empty data-testid='empty'>
                <EmptyMedia data-testid='media' variant='icon' />
            </Empty>
        );

        expect(screen.getByTestId('empty')).toHaveAttribute('data-size', 'default');
        expect(screen.getByTestId('empty')).not.toHaveAttribute('data-variant');
        expect(screen.getByTestId('media')).toHaveAttribute('data-variant', 'icon');
        expect(screen.getByTestId('media')).toHaveAttribute('data-ui', 'empty');
    });

    it('names multi-word primitives and state in kebab case', () => {
        render(<Card title='Details' actions={<button type='button'>Edit</button>} />);

        expect(screen.getByRole('heading', { name: 'Details' }).closest('[data-part="root"]')).toHaveAttribute(
            'data-titled',
            ''
        );
        expect(getSkin('DropdownMenuItem', { danger: true, described: false, checked: false })('root')).toMatchObject({
            'data-ui': 'dropdown-menu-item',
            'data-danger': '',
        });
        expect(
            getSkin('Select', { error: false, multiple: false, selected: false, richLabel: true, searching: false })(
                'root'
            )
        ).toMatchObject({
            'data-rich-label': '',
        });
    });
});

describe('replacement components', () => {
    it('renders a presentational replacement with the props the primitive received', () => {
        const received: ButtonProps[] = [];
        const onClick = vi.fn();

        registerSkins('aurora', {
            Button: (props: ButtonProps) => {
                received.push(props);

                return <span>{props.children}</span>;
            },
        });
        render(
            <Button color='green' isLoading onClick={onClick} className='w-full'>
                Save
            </Button>
        );

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
        expect(screen.getByText('Save').tagName).toBe('SPAN');
        expect(received.at(-1)).toEqual({
            color: 'green',
            isLoading: true,
            onClick,
            className: 'w-full',
            children: 'Save',
        });
    });

    it('uses the topmost replacement, while skins of the layers still resolve', () => {
        registerSkins('parent', { Badge: ({ children }: BadgeProps) => <em>{children}</em> });
        registerSkins('aurora', {
            Badge: ({ children }: BadgeProps) => <strong>{children}</strong>,
            Button: { root: 'pill' },
        });
        render(
            <>
                <Badge>New</Badge>
                <Button>Go</Button>
            </>
        );

        expect(screen.getByText('New').tagName).toBe('STRONG');
        expect(screen.getByRole('button', { name: 'Go' })).toHaveClass('pill');
    });

    it('keeps Base UI primitives in core', () => {
        const Replacement = () => <span>replaced</span>;

        // @ts-expect-error -- a Base UI primitive takes a skin, not a component.
        const ui: ThemeSkins = { Checkbox: Replacement };

        registerSkins('aurora', ui);
        render(<Checkbox checked onChange={() => {}} aria-label='Agree' />);

        expect(screen.getByRole('checkbox', { name: 'Agree' })).toHaveAttribute('data-ui', 'checkbox');
        expect(screen.queryByText('replaced')).not.toBeInTheDocument();
    });
});

describe('classic', () => {
    it('has a recipe for every part of every primitive', () => {
        for (const [name, parts] of Object.entries(UI_PRIMITIVE_PARTS)) {
            expect(Object.keys(Reflect.get(classicSkins, name)).sort(), name).toEqual([...parts].sort());
        }

        expect(Object.keys(classicSkins).sort()).toEqual(Object.keys(UI_PRIMITIVE_PARTS).sort());
    });
});
