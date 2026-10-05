import React from 'react';

interface Props {
    navigation: React.ReactNode;
    children?: React.ReactNode;
}

/** Stacked by default; a sidebar row when `<html data-sub-navigation="side">` is set. */
const SubNavigationLayout = ({ navigation, children }: Props) => (
    <div
        className={
            'side-navigation:mx-auto side-navigation:flex side-navigation:w-full side-navigation:max-w-panel side-navigation:items-start'
        }
    >
        {navigation}
        <div className={'side-navigation:min-w-0 side-navigation:flex-1'}>{children}</div>
    </div>
);

export default SubNavigationLayout;
