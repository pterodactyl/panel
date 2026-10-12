export interface CssToken {
    readonly name: string;
    readonly value: string;
}

interface TokenRule {
    readonly selector: string;
    /** The class an element needs for the selector to match, when it starts with one. */
    readonly className: string | null;
    readonly tokens: readonly string[];
}

const TOKEN_REFERENCE = /var\(\s*(--[\w-]+)/g;
const LEADING_CLASS = /^\.((?:\\.|[\w-])+)/;
const STATES = /:(?:hover|focus-visible|focus-within|focus|active)\b/g;
const ELEMENT_LIMIT = 150;

const isStyleRule = (rule: CSSRule): rule is CSSStyleRule => 'selectorText' in rule;
const isGroupingRule = (rule: CSSRule): rule is CSSGroupingRule => 'cssRules' in rule;
/** Declarations nested in an at-rule inside a style rule, such as `&:hover { @media (hover: hover) { … } }`. */
const hasDeclarations = (rule: CSSRule): rule is CSSRule & Pick<CSSStyleRule, 'style'> => 'style' in rule;
const withoutArguments = (selector: string) => selector.replaceAll(/\([^)]*\)/g, '');

// Tailwind's own `--tw-*` plumbing is not something a theme sets.
const references = (cssText: string): string[] =>
    [...cssText.matchAll(TOKEN_REFERENCE)].map((match) => match[1]).filter((name) => !name.startsWith('--tw-'));

/** A selector that matches the element itself, not one of its descendants: `.a:hover`, not `.a .b`. */
const isCompound = (selector: string) => !/[\s>+~,]/.test(withoutArguments(selector));

/** The class a compound selector requires, to skip `matches()` on elements that lack it. */
const leadingClass = (selector: string) =>
    isCompound(selector) ? (LEADING_CLASS.exec(selector)?.[1]?.replaceAll(/\\(.)/g, '$1') ?? null) : null;

/** A nested rule's selector is relative to its parent's, as Tailwind emits variants: `.x { &:hover { … } }`. */
function nestedSelector(selector: string, parent: TokenRule | null): string {
    if (!parent) {
        return selector;
    }

    return selector.includes('&')
        ? selector.replaceAll('&', `:is(${parent.selector})`)
        : `:is(${parent.selector}) ${selector}`;
}

function requiredClass(rule: CSSStyleRule, selector: string, parent: TokenRule | null): string | null {
    if (!parent) {
        return leadingClass(selector);
    }

    // `&:hover` still needs the parent's class on the element itself; `& .icon` does not.
    return rule.selectorText.startsWith('&') && isCompound(rule.selectorText) ? parent.className : null;
}

function collectRules(rules: CSSRuleList, into: TokenRule[], parent: TokenRule | null = null): void {
    for (const rule of rules) {
        let scope = parent;

        if (isStyleRule(rule)) {
            const selector = nestedSelector(rule.selectorText, parent);

            scope = {
                selector,
                className: requiredClass(rule, selector, parent),
                tokens: references(rule.style.cssText),
            };

            if (scope.tokens.length) {
                into.push(scope);
            }
        } else if (parent && hasDeclarations(rule)) {
            const tokens = references(rule.style.cssText);

            if (tokens.length) {
                into.push({ ...parent, tokens });
            }
        }

        if (isGroupingRule(rule)) {
            collectRules(rule.cssRules, into, scope);
        }
    }
}

function tokenRules(document: Document): TokenRule[] {
    const rules: TokenRule[] = [];

    for (const sheet of document.styleSheets) {
        try {
            collectRules(sheet.cssRules, rules);
        } catch {
            // A cross-origin stylesheet cannot be read.
        }
    }

    return rules;
}

function matches(element: Element, selector: string): boolean {
    try {
        return element.matches(selector) || element.matches(selector.replaceAll(STATES, ''));
    } catch {
        return false;
    }
}

function subtree(roots: readonly Element[]): Element[] {
    const elements: Element[] = [];
    const queue = [...roots];

    while (queue.length && elements.length < ELEMENT_LIMIT) {
        const element = queue.shift()!;

        elements.push(element);
        queue.push(...element.children);
    }

    return elements;
}

/**
 * The custom properties the rules matching these elements read, including their hover and focus
 * states, with their values resolved on the first element.
 */
export function tokensInUse(roots: readonly Element[]): CssToken[] {
    const [first] = roots;

    if (!first) {
        return [];
    }

    const rules = tokenRules(first.ownerDocument);
    const elements = subtree(roots);
    const applies = (rule: TokenRule, element: Element) =>
        (!rule.className || element.classList.contains(rule.className)) && matches(element, rule.selector);
    const names = new Set([
        ...elements.flatMap((element) => rules.filter((rule) => applies(rule, element)).flatMap((rule) => rule.tokens)),
        ...elements.flatMap((element) => references(element.getAttribute('style') ?? '')),
    ]);
    const style = first.ownerDocument.defaultView?.getComputedStyle(first);

    return [...names]
        .sort((a, b) => a.localeCompare(b))
        .map((name) => ({ name, value: style?.getPropertyValue(name).trim() ?? '' }));
}

const DECLARATION = /(--[\w-]+)\s*:\s*([^;}]+)/g;

export interface ThemeSettingTokens {
    /** Set by the settings style and any live preview of unsaved settings. */
    readonly tokens: readonly CssToken[];
    readonly attributes: readonly CssToken[];
}

/** What the active theme's settings put on `<html>`: tokens in the settings style, and attributes. */
export function themeSettingTokens(document: Document): ThemeSettingTokens {
    const declared = document.getElementById('theme-settings')?.textContent ?? '';
    const inline = document.documentElement.getAttribute('style') ?? '';

    return {
        tokens: [...`${declared};${inline}`.matchAll(DECLARATION)].map(([, name, value]) => ({
            name,
            value: value.trim(),
        })),
        attributes: [...document.documentElement.attributes]
            .filter((attribute) => attribute.name.startsWith('data-') || attribute.name === 'class')
            .map((attribute) => ({ name: attribute.name, value: attribute.value })),
    };
}
