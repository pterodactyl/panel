/** @vitest-environment jsdom */
import { cleanup, fireEvent, render } from '@testing-library/react';
import { useEffect, useState } from 'react';
import { afterEach, expect, it } from 'vitest';
import Portal, { PortalContainerContext, useViewDocument } from './Portal';

afterEach(cleanup);

/** Closes on Escape pressed anywhere in the view's window, as a theme's drawer does. */
function Drawer() {
    const { window: view } = useViewDocument();
    const [open, setOpen] = useState(true);

    useEffect(() => {
        const close = (event: KeyboardEvent) => event.key === 'Escape' && setOpen(false);

        view.addEventListener('keydown', close);

        return () => view.removeEventListener('keydown', close);
    }, [view]);

    return open ? <Portal>drawer</Portal> : null;
}

it('gives a view the window and document of the preview frame it renders in', () => {
    const frame = document.body.appendChild(document.createElement('iframe'));
    const preview = frame.contentDocument!;
    const overlays = preview.body.appendChild(preview.createElement('div'));

    render(
        <PortalContainerContext.Provider value={overlays}>
            <Drawer />
        </PortalContainerContext.Provider>
    );

    expect(overlays).toHaveTextContent('drawer');
    fireEvent.keyDown(document, { key: 'Escape' });
    expect(overlays).toHaveTextContent('drawer');
    fireEvent.keyDown(preview, { key: 'Escape' });
    expect(overlays).not.toHaveTextContent('drawer');
    frame.remove();
});

it('gives a view the page itself outside a preview, and portals into the body without a portal element', () => {
    const { container } = render(<Drawer />);

    expect(document.body).toHaveTextContent('drawer');
    expect(container).toBeEmptyDOMElement();
    fireEvent.keyDown(document, { key: 'Escape' });
    expect(document.body).not.toHaveTextContent('drawer');
});
