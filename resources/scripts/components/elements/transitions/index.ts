import { Transition as TransitionComponent } from '@headlessui/react';
import FadeTransition from '@/components/elements/transitions/FadeTransition';

const Transition = Object.assign(TransitionComponent, {
    Fade: FadeTransition,
});

export { Transition };
