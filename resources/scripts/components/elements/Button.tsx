import React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { Link } from '@tanstack/react-router';
import { cn } from '@/lib/cn';
import Spinner from '@/components/elements/Spinner';

interface Props {
    isLoading?: boolean;
    size?: 'xsmall' | 'small' | 'large' | 'xlarge';
    color?: 'green' | 'red' | 'orange' | 'primary' | 'grey';
    isSecondary?: boolean;
}

const buttonStyle = cva(
    'relative inline-block rounded-sm border p-2 text-sm uppercase tracking-wide transition-colors duration-150 disabled:cursor-default disabled:opacity-50',
    {
        variants: {
            color: {
                primary:
                    'bg-primary border-primary text-primary-foreground [&:hover:not(:disabled)]:bg-primary/80 [&:hover:not(:disabled)]:border-primary',
                grey: 'border-border bg-popover text-foreground [&:hover:not(:disabled)]:bg-secondary [&:hover:not(:disabled)]:border-secondary',
                green: 'border-success bg-success text-success-foreground [&:hover:not(:disabled)]:bg-success/80 [&:hover:not(:disabled)]:border-success',
                red: 'border-destructive bg-destructive text-destructive-foreground [&:hover:not(:disabled)]:bg-destructive/80 [&:hover:not(:disabled)]:border-destructive',
                orange: 'border-caution bg-caution text-caution-foreground [&:hover:not(:disabled)]:bg-caution/80 [&:hover:not(:disabled)]:border-caution',
            },
            size: {
                xsmall: 'px-2 py-1 text-xs',
                small: 'px-4 py-2',
                large: 'p-4 text-sm',
                xlarge: 'p-4 w-full',
            },
            isSecondary: {
                true: 'border-border bg-transparent text-foreground [&:hover:not(:disabled)]:border-popover [&:hover:not(:disabled)]:bg-popover [&:hover:not(:disabled)]:text-foreground',
                false: '',
            },
        },
        compoundVariants: [
            {
                isSecondary: true,
                color: 'primary',
                class: '[&:hover:not(:disabled)]:bg-primary [&:hover:not(:disabled)]:border-primary [&:hover:not(:disabled)]:text-primary-foreground',
            },
            {
                isSecondary: true,
                color: 'red',
                class: '[&:hover:not(:disabled)]:bg-destructive [&:hover:not(:disabled)]:border-destructive [&:hover:not(:disabled)]:text-destructive-foreground',
            },
            {
                isSecondary: true,
                color: 'green',
                class: '[&:hover:not(:disabled)]:bg-success [&:hover:not(:disabled)]:border-success [&:hover:not(:disabled)]:text-success-foreground',
            },
            {
                isSecondary: true,
                color: 'orange',
                class: '[&:hover:not(:disabled)]:bg-caution [&:hover:not(:disabled)]:border-caution [&:hover:not(:disabled)]:text-caution-foreground',
            },
            {
                isSecondary: false,
                color: 'primary',
                class: '[&:active:not(:disabled)]:bg-primary/70 [&:active:not(:disabled)]:border-primary/70',
            },
            {
                isSecondary: false,
                color: 'green',
                class: '[&:active:not(:disabled)]:bg-success/70 [&:active:not(:disabled)]:border-success',
            },
            {
                isSecondary: false,
                color: 'red',
                class: '[&:active:not(:disabled)]:bg-destructive/70 [&:active:not(:disabled)]:border-destructive',
            },
            {
                isSecondary: false,
                color: 'orange',
                class: '[&:active:not(:disabled)]:bg-caution/70 [&:active:not(:disabled)]:border-caution',
            },
        ],
        defaultVariants: {
            color: 'primary',
            size: 'small',
            isSecondary: false,
        },
    }
);

type StyleProps = VariantProps<typeof buttonStyle>;

const buttonClassName = ({
    color,
    size,
    isSecondary,
    className,
}: Omit<Props, 'isLoading'> & { className?: string }): string =>
    cn(
        buttonStyle({
            // A secondary button without a colour skips the default primary fill.
            color: isSecondary && !color ? undefined : (color as StyleProps['color']),
            size: (size as StyleProps['size']) ?? 'small',
            isSecondary: !!isSecondary,
        }),
        className
    );

type StyleableProps = Omit<Props, 'isLoading'>;

type ButtonStyleProps<E extends React.ElementType = 'button'> = {
    as?: E;
    ref?: React.ComponentPropsWithRef<E>['ref'];
} & StyleableProps &
    Omit<React.ComponentPropsWithoutRef<E>, keyof StyleableProps | 'as'>;

const ButtonStyle = <E extends React.ElementType = 'button'>({
    as,
    color,
    size,
    isSecondary,
    className,
    ref,
    ...props
}: ButtonStyleProps<E>) => {
    const Component = (as || 'button') as React.ElementType;

    return <Component ref={ref} className={buttonClassName({ color, size, isSecondary, className })} {...props} />;
};

type ComponentProps = Omit<React.ComponentPropsWithRef<'button'>, keyof Props> & Props;

/** Defaults to `type="button"`; `isLoading` shows a spinner and disables the button. */
const Button = ({ children, isLoading, disabled, type = 'button', ref, ...props }: ComponentProps) => (
    <ButtonStyle ref={ref} type={type} disabled={disabled || isLoading} aria-busy={isLoading || undefined} {...props}>
        {isLoading && (
            <span className='flex absolute justify-center items-center w-full h-full left-0 top-0'>
                <Spinner size='small' />
            </span>
        )}
        <span className={isLoading ? 'text-transparent' : undefined}>{children}</span>
    </ButtonStyle>
);

Button.displayName = 'Button';

const TextButton = ({ ref, ...props }: ComponentProps) => <Button ref={ref} color='grey' {...props} />;

TextButton.displayName = 'Button.Text';

const DangerButton = ({ ref, ...props }: ComponentProps) => <Button ref={ref} color='red' {...props} />;

DangerButton.displayName = 'Button.Danger';

// Assigned as static properties; Object.assign would hide the component from Fast Refresh.
const ButtonWithVariants = Button as typeof Button & {
    Text: typeof TextButton;
    Danger: typeof DangerButton;
};

ButtonWithVariants.Text = TextButton;
ButtonWithVariants.Danger = DangerButton;

type LinkProps = Omit<React.JSX.IntrinsicElements['a'], 'ref' | keyof Props> & Props;

const LinkButton = (props: LinkProps) => <ButtonStyle as='a' {...props} />;

const RouterLinkButton = (props: ButtonStyleProps<typeof Link>) => <ButtonStyle as={Link} {...props} />;

export { LinkButton, RouterLinkButton, ButtonStyle, buttonClassName };
export default ButtonWithVariants;
