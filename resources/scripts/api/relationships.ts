// Fractal returns a NullResource for any include the viewer lacks permission for.

interface NullResourceLike {
    object: 'null_resource';
}

export function relationshipData<T>(relationship: { data: T[] } | NullResourceLike | undefined): T[] {
    return relationship && 'data' in relationship ? relationship.data : [];
}

export function relationshipAttributes<A>(relationship: { attributes: A | null } | undefined): A | undefined {
    return relationship?.attributes ?? undefined;
}
