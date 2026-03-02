@extends('layouts.base')

@section('title')
    World Manager
@endsection

@section('content')
    <style>
        .world-manager { color: #e5e7eb; background: #111827; padding: 1.25rem; border-radius: 12px; }
        .wm-toolbar { display: flex; gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem; }
        .wm-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); }
        .wm-card { background: #1f2937; border: 1px solid #374151; border-radius: 10px; padding: 1rem; }
        .wm-card__header { display: flex; justify-content: space-between; align-items: center; }
        .wm-badge { background: #2563eb; border-radius: 999px; padding: 0.15rem 0.6rem; font-size: .75rem; }
    </style>

    <div class="world-manager">
        <div class="wm-toolbar">
            <input type="search" id="wm-search" placeholder="Search CurseForge worlds..." />
            <select id="wm-version">
                <option value="">All Versions</option>
                <option value="1.20.1">1.20.1</option>
                <option value="1.19.4">1.19.4</option>
            </select>
            <button id="wm-search-button" class="btn btn-primary">Search</button>
        </div>

        <div id="wm-results" class="wm-grid"></div>

        <section class="wm-installed">
            <h3>Installed Worlds</h3>
            <div id="wm-installed-list"></div>
        </section>
    </div>

    <template id="wm-card-template">
        <article class="wm-card">
            <div class="wm-card__header">
                <h4 class="wm-card__title"></h4>
                <span class="wm-badge"></span>
            </div>
            <p class="wm-card__summary"></p>
            <button class="btn btn-sm btn-success wm-install-button">Install</button>
        </article>
    </template>

    <script>
        (async function () {
            const searchButton = document.getElementById('wm-search-button');
            const searchInput = document.getElementById('wm-search');
            const versionInput = document.getElementById('wm-version');
            const results = document.getElementById('wm-results');
            const template = document.getElementById('wm-card-template');

            async function runSearch() {
                const params = new URLSearchParams({ q: searchInput.value, minecraft_version: versionInput.value });
                const response = await fetch(`/api/client/servers/${window.ServerContext?.id}/worlds/search?${params}`);
                const payload = await response.json();

                results.innerHTML = '';
                (payload.data || []).forEach((world) => {
                    const node = template.content.cloneNode(true);
                    node.querySelector('.wm-card__title').textContent = world.name;
                    node.querySelector('.wm-badge').textContent = `Downloads: ${world.downloadCount ?? 0}`;
                    node.querySelector('.wm-card__summary').textContent = world.summary ?? 'No description provided.';
                    node.querySelector('.wm-install-button').addEventListener('click', () => {
                        window.alert(`Open version modal for ${world.name}`);
                    });
                    results.appendChild(node);
                });
            }

            searchButton?.addEventListener('click', runSearch);
        })();
    </script>
@endsection
