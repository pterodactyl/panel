import React from 'react';
import { cn } from '@/lib/cn';

interface Props {
    navigation: React.ReactNode;
    className?: string;
    children?: React.ReactNode;
}

/** Stacked by default; a sidebar row when `<html data-sub-navigation="side">` is set. */
const SubNavigationLayout = ({ navigation, className, children }: Props) => (
    <div
        className={cn(
            'side-navigation:mx-auto side-navigation:flex side-navigation:w-full side-navigation:max-w-panel side-navigation:items-start',
            className
        )}
    >
        {navigation}
        <div className='side-navigation:min-w-0 side-navigation:flex-1'>{children}</div>
    </div>
);

export default SubNavigationLayout;
