/// <reference types="vite/client" />

// Injected via Vite `define` for cache-busting locale requests.
interface ImportMetaEnv {
    readonly WEBPACK_BUILD_HASH: string;
}
