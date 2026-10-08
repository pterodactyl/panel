// @vitest-environment jsdom

import { globSync, readFileSync } from 'node:fs';
import postcss from 'postcss';
import { expect, it } from 'vitest';
import { paletteUtility } from './rules/design-tokens';

it('uses semantic colors and radii in custom CSS outside the theme definition', () => {
    for (const path of globSync('resources/scripts/**/*.css').map((path) => path.replaceAll('\\', '/'))) {
        const stylesheet = postcss.parse(readFileSync(path, 'utf8'), { from: path });

        stylesheet.walkDecls((declaration) => {
            if (path === 'resources/scripts/assets/tailwind.css') {
                return;
            }

            if (
                /^(?:color|background(?:-color)?|border(?:-[a-z]+)?-color|outline-color|fill|stroke)$/.test(
                    declaration.prop
                )
            ) {
                expect(declaration.value, `${path}:${declaration.source?.start?.line}`).toMatch(
                    /^(?:var\(|currentColor\b|transparent\b|none\b|inherit\b)/i
                );
            }

            if (/^(?:-webkit-)?border-radius$/.test(declaration.prop)) {
                expect(declaration.value, `${path}:${declaration.source?.start?.line}`).toMatch(/^var\(/);
            }
        });
        stylesheet.walkAtRules('apply', (rule) => {
            expect(rule.params, path).not.toMatch(paletteUtility);
        });
    }
});

it('uses semantic color utilities in Blade template classes', () => {
    for (const path of globSync('resources/views/**/*.blade.php').map((path) => path.replaceAll('\\', '/'))) {
        if (path.startsWith('resources/views/scribe/')) {
            continue;
        }

        const template = document.createElement('template');

        template.innerHTML = readFileSync(path, 'utf8');
        for (const element of template.content.querySelectorAll('[class]')) {
            expect(element.getAttribute('class'), path).not.toMatch(paletteUtility);
        }
    }
});
