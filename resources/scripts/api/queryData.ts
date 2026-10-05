type ListData<TItem> = {
    data: TItem[];
};

type ListItem<TQueryData extends ListData<unknown>> = TQueryData['data'][number];

export const upsertListItem = <TQueryData extends ListData<unknown>>(
    current: TQueryData | undefined,
    item: ListItem<TQueryData>,
    matches: (current: ListItem<TQueryData>, next: ListItem<TQueryData>) => boolean,
    create?: (item: ListItem<TQueryData>) => TQueryData
): TQueryData | undefined => {
    if (!current) {
        return create?.(item);
    }

    const data = current.data.some((currentItem) => matches(currentItem, item))
        ? current.data.map((currentItem) => (matches(currentItem, item) ? item : currentItem))
        : [...current.data, item];

    return { ...current, data };
};

export const removeListItems = <TQueryData extends ListData<unknown>>(
    current: TQueryData | undefined,
    matches: (item: ListItem<TQueryData>) => boolean
): TQueryData | undefined => (current ? { ...current, data: current.data.filter((item) => !matches(item)) } : current);

export const updateListItems = <TQueryData extends ListData<unknown>>(
    current: TQueryData | undefined,
    matches: (item: ListItem<TQueryData>) => boolean,
    updater: (item: ListItem<TQueryData>) => ListItem<TQueryData>
): TQueryData | undefined =>
    current ? { ...current, data: current.data.map((item) => (matches(item) ? updater(item) : item)) } : current;

export const mapListItems = <TQueryData extends ListData<unknown>>(
    current: TQueryData | undefined,
    mapper: (item: ListItem<TQueryData>) => ListItem<TQueryData>
): TQueryData | undefined => (current ? { ...current, data: current.data.map(mapper) } : current);
