import React from 'react';
import { cn } from '@/lib/cn';

const subNavigationClass = [
    'w-full bg-card shadow-sm overflow-x-auto scrollbar-none [&::-webkit-scrollbar]:hidden',
    '[&>div]:mx-auto [&>div]:flex [&>div]:max-w-panel [&>div]:items-center [&>div]:px-2 [&>div]:text-sm',
    '[&>div>a]:inline-block [&>div>a]:py-3 [&>div>a]:px-4 [&>div>a]:text-muted-foreground [&>div>a]:no-underline [&>div>a]:whitespace-nowrap [&>div>a]:transition-colors [&>div>a]:duration-150',
    '[&>div>div]:inline-block [&>div>div]:py-3 [&>div>div]:px-4 [&>div>div]:text-muted-foreground [&>div>div]:no-underline [&>div>div]:whitespace-nowrap [&>div>div]:transition-colors [&>div>div]:duration-150',
    '[&>div>a:not(:first-of-type)]:ml-2 [&>div>div:not(:first-of-type)]:ml-2',
    '[&>div>a:hover]:text-foreground [&>div>div:hover]:text-foreground',
    '[&>div>a:active]:text-foreground [&>div>a.active]:text-foreground [&>div>a[data-status=active]]:text-foreground',
    '[&>div>div:active]:text-foreground [&>div>div.active]:text-foreground [&>div>div[data-status=active]]:text-foreground',
    '[&>div>a:active]:shadow-navigation-active [&>div>a.active]:shadow-navigation-active [&>div>a[data-status=active]]:shadow-navigation-active',
    '[&>div>div:active]:shadow-navigation-active [&>div>div.active]:shadow-navigation-active [&>div>div[data-status=active]]:shadow-navigation-active',
].join(' ');

// Applies when `<html data-sub-navigation="side">` is set (lg and up).
const sideNavigationClass = [
    'side-navigation:sticky side-navigation:top-0 side-navigation:max-h-dvh side-navigation:w-sidebar side-navigation:shrink-0',
    'side-navigation:overflow-x-visible side-navigation:overflow-y-auto side-navigation:bg-transparent side-navigation:px-4 side-navigation:py-6 side-navigation:shadow-none',
    'side-navigation:[&>div]:flex-col side-navigation:[&>div]:items-stretch side-navigation:[&>div]:gap-0.5 side-navigation:[&>div]:px-0',
    'side-navigation:[&>div>a]:block side-navigation:[&>div>a]:rounded-md side-navigation:[&>div>a]:px-3 side-navigation:[&>div>a]:py-2',
    'side-navigation:[&>div>div]:block side-navigation:[&>div>div]:rounded-md side-navigation:[&>div>div]:px-3 side-navigation:[&>div>div]:py-2',
    'side-navigation:[&>div>a:not(:first-of-type)]:ml-0 side-navigation:[&>div>div:not(:first-of-type)]:ml-0',
    'side-navigation:[&>div>a:hover]:bg-card side-navigation:[&>div>div:hover]:bg-card',
    'side-navigation:[&>div>a:active]:bg-popover side-navigation:[&>div>a.active]:bg-popover side-navigation:[&>div>a[data-status=active]]:bg-popover',
    'side-navigation:[&>div>div:active]:bg-popover side-navigation:[&>div>div.active]:bg-popover side-navigation:[&>div>div[data-status=active]]:bg-popover',
    'side-navigation:[&>div>a:hover]:shadow-none side-navigation:[&>div>a:active]:shadow-none side-navigation:[&>div>a.active]:shadow-none side-navigation:[&>div>a[data-status=active]]:shadow-none',
    'side-navigation:[&>div>div:hover]:shadow-none side-navigation:[&>div>div:active]:shadow-none side-navigation:[&>div>div.active]:shadow-none side-navigation:[&>div>div[data-status=active]]:shadow-none',
].join(' ');

const SubNavigation = ({ className, ...props }: React.ComponentProps<'div'>) => (
    <div className={cn(subNavigationClass, sideNavigationClass, className)} {...props} />
);
SubNavigation.displayName = 'SubNavigation';

export default SubNavigation;
