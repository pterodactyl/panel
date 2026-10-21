import { getSearchQuery, searchPanelOpen, setSearchQuery } from '@codemirror/search';
import { ViewPlugin, type EditorView, type ViewUpdate } from '@codemirror/view';

class SearchScrollbarMarkersPlugin {
    readonly dom = document.createElement('div');

    constructor(view: EditorView) {
        this.dom.className = 'cm-search-scrollbar-markers';
        view.dom.appendChild(this.dom);
        this.refresh(view);
    }

    update(update: ViewUpdate) {
        const queryChanged = update.transactions.some((transaction) =>
            transaction.effects.some((effect) => effect.is(setSearchQuery))
        );

        if (update.docChanged || update.selectionSet || update.viewportChanged || queryChanged) {
            this.refresh(update.view);
        }
    }

    refresh(view: EditorView) {
        this.dom.replaceChildren();

        if (!searchPanelOpen(view.state)) {
            return;
        }

        const query = getSearchQuery(view.state);

        if (!query.valid) {
            return;
        }

        const contentHeight = Math.max(view.contentHeight, 1);
        const cursor = query.getCursor(view.state);
        let count = 0;

        while (count < 1000) {
            const result = cursor.next();

            if (result.done) {
                break;
            }

            const { from, to } = result.value;
            const block = view.lineBlockAt(from);
            const marker = document.createElement('span');
            const selected = view.state.selection.ranges.some((range) => range.from === from && range.to === to);

            marker.className = selected
                ? 'cm-search-scrollbar-marker cm-search-scrollbar-marker-selected'
                : 'cm-search-scrollbar-marker';
            marker.style.top = `${Math.min(100, Math.max(0, (block.top / contentHeight) * 100))}%`;
            this.dom.appendChild(marker);
            count++;
        }
    }

    destroy() {
        this.dom.remove();
    }
}

export const searchScrollbarMarkers: ViewPlugin<SearchScrollbarMarkersPlugin> = ViewPlugin.fromClass(
    SearchScrollbarMarkersPlugin,
    {}
);
