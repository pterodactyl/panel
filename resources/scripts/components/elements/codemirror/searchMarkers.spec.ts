/** @vitest-environment jsdom */
import { closeSearchPanel, openSearchPanel, search, SearchQuery, setSearchQuery } from '@codemirror/search';
import { EditorView } from '@codemirror/view';
import { afterEach, expect, it } from 'vitest';
import { searchScrollbarMarkers } from '@/components/elements/codemirror/searchMarkers';

let view: EditorView | undefined;

afterEach(() => view?.destroy());

it('marks every match while the search panel is open and clears them once it closes', () => {
    view = new EditorView({ doc: 'ab\nab\nab', extensions: [search(), searchScrollbarMarkers], parent: document.body });

    const markers = () => view!.dom.querySelectorAll('.cm-search-scrollbar-marker').length;

    openSearchPanel(view);
    view.dispatch({ effects: setSearchQuery.of(new SearchQuery({ search: 'ab' })) });
    expect(markers()).toBe(3);

    closeSearchPanel(view);
    expect(markers()).toBe(0);

    openSearchPanel(view);
    expect(markers()).toBe(3);
});
