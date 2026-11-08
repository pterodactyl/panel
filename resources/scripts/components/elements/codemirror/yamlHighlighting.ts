import { syntaxTree } from '@codemirror/language';
import type { Range } from '@codemirror/state';
import { Decoration, ViewPlugin, type DecorationSet, type EditorView, type ViewUpdate } from '@codemirror/view';

class YamlScalarHighlightingPlugin {
    decorations: DecorationSet;

    constructor(view: EditorView) {
        this.decorations = this.buildDecorations(view);
    }

    update(update: ViewUpdate) {
        // The parser finishes large documents in the background, so a new tree needs new decorations too.
        if (update.docChanged || update.viewportChanged || syntaxTree(update.startState) !== syntaxTree(update.state)) {
            this.decorations = this.buildDecorations(update.view);
        }
    }

    buildDecorations(view: EditorView): DecorationSet {
        const decorations: Range<Decoration>[] = [];

        for (const { from, to } of view.visibleRanges) {
            syntaxTree(view.state).iterate({
                from,
                to,
                enter: (node) => {
                    if (node.name !== 'Literal' || node.node.parent?.name === 'Key') {
                        return;
                    }

                    const value = view.state.doc.sliceString(node.from, node.to).trim();
                    let className: string | undefined;

                    if (/^(?:true|false|yes|no|on|off)$/i.test(value)) {
                        className = 'cm-yaml-boolean';
                    } else if (/^(?:null|~)$/i.test(value)) {
                        className = 'cm-yaml-null';
                    } else if (
                        /^[+-]?(?:(?:[0-9][0-9_]*)(?:\.[0-9_]*)?|\.[0-9_]+)(?:[eE][+-]?[0-9]+)?$/.test(value) ||
                        /^[+-]?0x[0-9a-f_]+$/i.test(value) ||
                        /^[+-]?0o[0-7_]+$/i.test(value) ||
                        /^[+-]?0b[01_]+$/i.test(value)
                    ) {
                        className = 'cm-yaml-number';
                    }

                    if (className) {
                        decorations.push(Decoration.mark({ class: className }).range(node.from, node.to));
                    }
                },
            });
        }

        return Decoration.set(decorations, true);
    }
}

export const yamlScalarHighlighting: ViewPlugin<YamlScalarHighlightingPlugin> = ViewPlugin.fromClass(
    YamlScalarHighlightingPlugin,
    { decorations: (plugin) => plugin.decorations }
);
