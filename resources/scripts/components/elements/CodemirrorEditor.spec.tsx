/** @vitest-environment jsdom */
import { act, cleanup, render } from '@testing-library/react';
import { createRef } from 'react';
import { EditorView } from '@codemirror/view';
import { afterEach, expect, it, vi } from 'vitest';
import CodemirrorEditor, { type CodemirrorEditorHandle } from './CodemirrorEditor';

afterEach(cleanup);

const viewOf = (container: HTMLElement) => {
    const view = EditorView.findFromDOM(container.querySelector<HTMLElement>('.cm-editor')!);

    if (!view) {
        throw new Error('The editor view is not mounted.');
    }

    return view;
};

it('mounts the editor as the direct child of the sized container', () => {
    const { container } = render(
        <CodemirrorEditor initialContent='alpha' mode='text/plain' onContentSaved={vi.fn()} />
    );

    const box = container.firstElementChild!;

    expect(box.className).toContain('overflow-hidden');
    expect(box.children).toHaveLength(1);
    expect(box.firstElementChild).toHaveClass('cm-editor');
    expect(box.querySelector('.cm-content')).not.toHaveAttribute('spellcheck', 'true');
});

it('keeps the view, its edits and history when initialContent changes', () => {
    const ref = createRef<CodemirrorEditorHandle>();
    const onContentChanged = vi.fn();
    const props = { ref, mode: 'text/plain', onContentSaved: vi.fn(), onContentChanged };
    const { container, rerender } = render(<CodemirrorEditor {...props} initialContent='alpha' />);
    const view = viewOf(container);

    act(() => view.dispatch({ changes: { from: 5, insert: ' beta' } }));
    expect(onContentChanged).toHaveBeenLastCalledWith('alpha beta');

    rerender(<CodemirrorEditor {...props} initialContent='saved on the server' />);

    expect(viewOf(container)).toBe(view);
    expect(ref.current?.getValue()).toBe('alpha beta');
});

it('runs the latest save handler for Mod-s', () => {
    const first = vi.fn();
    const latest = vi.fn();
    const { container, rerender } = render(<CodemirrorEditor mode='text/plain' onContentSaved={first} />);

    rerender(<CodemirrorEditor mode='text/plain' onContentSaved={latest} />);

    container
        .querySelector('.cm-content')!
        .dispatchEvent(new KeyboardEvent('keydown', { key: 's', ctrlKey: true, bubbles: true }));

    expect(first).not.toHaveBeenCalled();
    expect(latest).toHaveBeenCalledOnce();
});

it('indents with Tab and outdents with Shift-Tab', () => {
    const { container } = render(
        <CodemirrorEditor initialContent='alpha' mode='text/plain' onContentSaved={vi.fn()} />
    );
    const view = viewOf(container);
    const content = container.querySelector('.cm-content')!;

    act(() => view.dispatch({ selection: { anchor: 0 } }));

    act(() => {
        content.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true }));
    });
    expect(view.state.doc.toString()).toBe('    alpha');

    act(() => {
        content.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', shiftKey: true, bubbles: true }));
    });
    expect(view.state.doc.toString()).toBe('alpha');
});

it('renders the search and replace panel with theme controls', () => {
    const { container } = render(<CodemirrorEditor mode='text/plain' onContentSaved={vi.fn()} />);

    container
        .querySelector('.cm-content')!
        .dispatchEvent(new KeyboardEvent('keydown', { key: 'f', ctrlKey: true, bubbles: true }));

    const searchPanel = container.querySelector('.cm-search');

    expect(searchPanel).not.toBeNull();
    expect(searchPanel?.querySelector('input[name="search"]')).not.toBeNull();
    expect(searchPanel?.querySelector('input[name="replace"]')).not.toBeNull();
    expect(searchPanel?.querySelector('button[name="close"]')).not.toBeNull();
    expect(searchPanel?.querySelectorAll('.cm-button').length).toBeGreaterThanOrEqual(4);
});

it('searches incrementally from the cursor and keeps the match while typing a replacement', () => {
    const { container } = render(
        <CodemirrorEditor initialContent='ab ab ab' mode='text/plain' onContentSaved={vi.fn()} />
    );
    const view = viewOf(container);

    container
        .querySelector('.cm-content')!
        .dispatchEvent(new KeyboardEvent('keydown', { key: 'f', ctrlKey: true, bubbles: true }));

    const type = (name: string, value: string) => {
        const field = container.querySelector<HTMLInputElement>(`.cm-search input[name="${name}"]`)!;

        field.value = value;
        act(() => {
            field.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));
        });
    };

    const selected = () => [view.state.selection.main.from, view.state.selection.main.to];

    type('search', 'a');
    expect(selected()).toEqual([0, 1]);

    type('search', 'ab');
    expect(selected()).toEqual([0, 2]);

    type('replace', 'x');
    type('replace', 'xy');
    expect(selected()).toEqual([0, 2]);
});
