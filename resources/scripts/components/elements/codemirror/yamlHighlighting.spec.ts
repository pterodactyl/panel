/** @vitest-environment jsdom */
import { yaml } from '@codemirror/lang-yaml';
import { ensureSyntaxTree } from '@codemirror/language';
import { EditorView } from '@codemirror/view';
import { afterEach, expect, it } from 'vitest';
import { yamlScalarHighlighting } from '@/components/elements/codemirror/yamlHighlighting';

let view: EditorView | undefined;

afterEach(() => view?.destroy());

it('colours booleans, nulls and numbers in values but not in keys', () => {
    view = new EditorView({
        doc: 'enabled: true\nport: 25565\nmotd: ~\n123: name\n',
        extensions: [yaml(), yamlScalarHighlighting],
        parent: document.body,
    });
    ensureSyntaxTree(view.state, view.state.doc.length);
    view.dispatch({});

    const classed = (name: string) =>
        [...view!.contentDOM.querySelectorAll(`.${name}`)].map((node) => node.textContent);

    expect(classed('cm-yaml-boolean')).toEqual(['true']);
    expect(classed('cm-yaml-number')).toEqual(['25565']);
    expect(classed('cm-yaml-null')).toEqual(['~']);
});
