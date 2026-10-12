// oxlint-disable-next-line rules/no-unsafe-dictionary-type -- stub until the UI kit lands.
export type ThemeSkins = Record<string, unknown>;

export function registerSkins(_layerId: string, _skins: ThemeSkins): void {}

export function skin<T>(_name: string, def: T): T {
    return def;
}
