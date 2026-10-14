import React from 'react';
import { useSkin } from '@/themes/skins';

export type LabelProps<E extends React.ElementType = 'label'> = {
    as?: E;
    isLight?: boolean;
} & Omit<React.ComponentPropsWithoutRef<E>, 'as' | 'isLight'>;

const Label = <E extends React.ElementType = 'label'>({ as, isLight, className, ...props }: LabelProps<E>) => {
    const Component = (as || 'label') as React.ElementType;
    const ui = useSkin('Label', { light: !!isLight });

    return <Component {...ui('root', className)} {...props} />;
};

export default Label;
