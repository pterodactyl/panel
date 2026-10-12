/** Works across documents, where `instanceof Element` does not. */
export const isElement = (node: EventTarget | null): node is Element =>
    !!node && 'nodeType' in node && node.nodeType === Node.ELEMENT_NODE;

const isContents = (element: Element) =>
    element.ownerDocument.defaultView?.getComputedStyle(element).display === 'contents';

/** The elements that draw a view: its wrapper and nested wrappers are `display: contents`. */
export function viewElements(wrapper: Element): Element[] {
    return [...wrapper.children].flatMap((child) => (isContents(child) ? viewElements(child) : [child]));
}

interface FrameOffset {
    readonly x: number;
    readonly y: number;
    readonly scale: number;
}

/** Where a document's coordinates sit in the top window, for views inside a workbench frame. */
function frameOffset(element: Element): FrameOffset {
    const frame = element.ownerDocument.defaultView?.frameElement;

    if (!frame || !(frame instanceof HTMLElement) || !frame.offsetWidth) {
        return { x: 0, y: 0, scale: 1 };
    }

    const rect = frame.getBoundingClientRect();
    const scale = rect.width / frame.offsetWidth;

    return { x: rect.left + frame.clientLeft * scale, y: rect.top + frame.clientTop * scale, scale };
}

/** The box a view's content covers in the top window, or null when it draws nothing. */
export function viewRect(wrapper: Element): DOMRect | null {
    const boxes: DOMRect[] = [];
    const visit = (node: Node) => {
        for (const child of node.childNodes) {
            if (isElement(child)) {
                if (isContents(child)) {
                    visit(child);
                } else {
                    boxes.push(child.getBoundingClientRect());
                }
            } else if (child.nodeType === Node.TEXT_NODE && child.textContent?.trim()) {
                const range = child.ownerDocument?.createRange();

                range?.selectNodeContents(child);
                boxes.push(range?.getBoundingClientRect() ?? new DOMRect());
            }
        }
    };

    visit(wrapper);

    const drawn = boxes.filter((box) => box.width > 0 || box.height > 0);

    if (!drawn.length) {
        return null;
    }

    const { x, y, scale } = frameOffset(wrapper);
    const left = Math.min(...drawn.map((box) => box.left));
    const top = Math.min(...drawn.map((box) => box.top));
    const right = Math.max(...drawn.map((box) => box.right));
    const bottom = Math.max(...drawn.map((box) => box.bottom));

    return new DOMRect(x + left * scale, y + top * scale, (right - left) * scale, (bottom - top) * scale);
}

/** The nearest view wrapper enclosing a node, itself included. */
export function closestView(node: Element): Element | null {
    return node.closest('[data-view]');
}

/** Views enclosing this one, nearest first. */
export function parentViews(wrapper: Element): Element[] {
    const parent = wrapper.parentElement?.closest('[data-view]');

    return parent ? [parent, ...parentViews(parent)] : [];
}
