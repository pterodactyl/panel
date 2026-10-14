/** @vitest-environment jsdom */
import { cleanup, render } from '@testing-library/react';
import { afterEach, expect, it } from 'vitest';
import Portal from './Portal';

afterEach(cleanup);

it('renders into the overlay layer of the page', () => {
    const layer = document.body.appendChild(Object.assign(document.createElement('div'), { id: 'modal-portal' }));

    render(<Portal>overlay</Portal>);

    expect(layer).toHaveTextContent('overlay');
    layer.remove();
});

it('renders into the body where the page has no overlay layer, as in a view test', () => {
    const { container } = render(<Portal>overlay</Portal>);

    expect(container).toBeEmptyDOMElement();
    expect(document.body).toHaveTextContent('overlay');
});
