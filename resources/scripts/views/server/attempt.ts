/** Runs a mutation whose failure the mutation itself reports, resolving whether it succeeded. */
export async function attempt(mutation: () => Promise<unknown>): Promise<boolean> {
    try {
        await mutation();

        return true;
    } catch {
        // Error toast is handled by the mutation.
        return false;
    }
}
