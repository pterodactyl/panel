import React from 'react';
import { Link } from '@tanstack/react-router';
import Spinner from '@/components/elements/Spinner';
import { getSkin, replaceable, useSkin } from '@/themes/skins';

export type ButtonColor = 'green' | 'red' | 'orange' | 'primary' | 'grey';
export type ButtonSize = 'xsmall' | 'small' | 'large' | 'xlarge';

interface Props {
    isLoading?: boolean;
    size?: ButtonSize;
    color?: ButtonColor;
    isSecondary?: boolean;
}

type StyleableProps = Omit<Props, 'isLoading'>;

const buttonState = ({ color, size, isSecondary }: StyleableProps, loading = false) => ({
    color: color ?? 'primary',
    size: size ?? 'small',
    secondary: !!isSecondary,
    loading,
});

/** The root classes of a button, for elements styled as one outside a component. */
const buttonClassName = ({ className, ...props }: StyleableProps & { className?: string }): string =>
    getSkin('Button', buttonState(props)).classes('root', className);

type ButtonStyleProps<E extends React.ElementType = 'button'> = {
    as?: E;
    ref?: React.ComponentPropsWithRef<E>['ref'];
} & StyleableProps &
    Omit<React.ComponentPropsWithoutRef<E>, keyof StyleableProps | 'as'>;

/** Any element, styled as a button. */
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
    const ui = useSkin('Button', buttonState({ color, size, isSecondary }));

    return <Component ref={ref} {...ui('root', className)} {...props} />;
};

export type ButtonProps = Omit<React.ComponentPropsWithRef<'button'>, keyof Props> & Props;

function CoreButton({
    children,
    isLoading,
    disabled,
    type = 'button',
    color,
    size,
    isSecondary,
    className,
    ref,
    ...props
}: ButtonProps) {
    const ui = useSkin('Button', buttonState({ color, size, isSecondary }, !!isLoading));

    return (
        <button
            ref={ref}
            {...ui('root', className)}
            type={type}
            disabled={disabled || isLoading}
            aria-busy={isLoading || undefined}
            {...props}
        >
            {isLoading && (
                <span {...ui('spinner')}>
                    <Spinner size='small' />
                </span>
            )}
            <span {...ui('label')}>{children}</span>
        </button>
    );
}

/** Defaults to `type="button"`; `isLoading` shows a spinner and disables the button. */
const Button = replaceable('Button', CoreButton);

const TextButton = ({ ref, ...props }: ButtonProps) => <Button ref={ref} color='grey' {...props} />;

TextButton.displayName = 'Button.Text';

const DangerButton = ({ ref, ...props }: ButtonProps) => <Button ref={ref} color='red' {...props} />;

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
