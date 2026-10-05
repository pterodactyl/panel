# Admin SPA (`/panel`)

React admin area that reuses the shared Panel data and UI architecture. It is the
only admin destination exposed by the frontend navigation. The legacy Blade panel at
`/admin` remains available by direct URL while the backend cutover is completed.

## How it's wired

- **Base route:** `/panel`, defined in `resources/scripts/router/routeTree.ts` under the
  authenticated route tree and rendered by `AdminLayout`.
- **Route loaders:** preload page-critical data through TanStack Query
  `queryOptions` exported from `api/**/queries.ts`.
- **Data layer:** `api/admin/*` calls the dedicated Admin API (`/api/admin/*`) with the
  first-party session cookie. The public `/api/application` API is not used.
- **State:** TanStack Query owns API data. Zustand/ServerContext are not used for admin
  API data.
- **Errors:** mutation and transient server errors use Sonner through domain query hooks
  and `notifyHttpError`; blocking screen errors render `ServerError`.

## Adding a domain (follow `users/`)

1. `api/admin/<domain>/get<Domain>.ts` calls the Admin API, maps response data, and
   returns typed data.
2. `api/admin/<domain>/queries.ts` exports shared `queryOptions`, query hooks, mutation
   hooks, cache updates, and Sonner error/success handling.
3. `components/admin/<domain>/<Domain>Container.tsx` renders `AdminContentBlock`,
   `Pagination`, rows, empty states, and blocking `ServerError` retry UI.
4. Register routes and page-critical loaders in `router/routeTree.ts`, using the same
   query options as the component hooks.
