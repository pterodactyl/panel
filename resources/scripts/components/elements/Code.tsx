import React from 'react';
import { useSkin } from '@/themes/skins';

export interface CodeProps {
    dark?: boolean | undefined;
    className?: string;
    children: React.ReactNode;
}

export default function Code({ dark, className, children }: CodeProps) {
    const ui = useSkin('Code', { dark: !!dark });

    return <code {...ui('root', className)}>{children}</code>;
}
