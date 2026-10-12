const frames = new Set<HTMLIFrameElement>();
const listeners = new Set<() => void>();
let snapshot: readonly HTMLIFrameElement[] = [];

const publish = () => {
    snapshot = [...frames];
    for (const listener of listeners) {
        listener();
    }
};

/** A same-origin frame, such as a workbench preview, whose views the inspector reaches too. */
export function registerInspectableFrame(frame: HTMLIFrameElement): () => void {
    frames.add(frame);
    publish();

    return () => {
        frames.delete(frame);
        publish();
    };
}

export function getInspectableFrames(): readonly HTMLIFrameElement[] {
    return snapshot;
}

export function subscribeInspectableFrames(listener: () => void): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}
