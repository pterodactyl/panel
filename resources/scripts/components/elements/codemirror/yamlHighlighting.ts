import { syntaxTree } from '@codemirror/language';
import type { Extension, Range } from '@codemirror/state';
import { Decoration, EditorView, ViewPlugin, type DecorationSet, type ViewUpdate } from '@codemirror/view';
import { resolveCodemirrorLanguage } from '@/components/elements/codemirror/languages';

class YamlScalarHighlightingPlugin {
    decorations: DecorationSet;

    constructor(view: EditorView) {
        this.decorations = this.buildDecorations(view);
    }

    update(update: ViewUpdate) {
        if (update.docChanged || update.viewportChanged) {
            this.decorations = this.buildDecorations(update.view);
        }
    }

    buildDecorations(view: EditorView): DecorationSet {
        const decorations: Range<Decoration>[] = [];

        syntaxTree(view.state).iterate({
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

        return Decoration.set(decorations, true);
    }
}

export const yamlScalarHighlighting: ViewPlugin<YamlScalarHighlightingPlugin> = ViewPlugin.fromClass(
    YamlScalarHighlightingPlugin,
    { decorations: (plugin) => plugin.decorations }
);

export const resolveEditorLanguage = (mime: string): Extension => {
    const language = resolveCodemirrorLanguage(mime);

    return mime === 'text/x-yaml' || mime === 'text/yaml' ? [language, yamlScalarHighlighting] : language;
};
