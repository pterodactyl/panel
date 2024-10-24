name: Docker

on:
  push:
    branches:
      - develop
      - 1.0-develop
  pull_request:
    branches:
      - develop
      - 1.0-develop
  release:
    types:
      - published

jobs:
  push:
    name: Push
    runs-on: ubuntu-20.04
    if: "!contains(github.ref, 'develop') || (!contains(github.event.head_commit.message, 'skip docker') && !contains(github.event.head_commit.message, 'docker skip'))"
    permissions:
      contents: read
      packages: write
    steps:
      - name: Code checkout
        uses: actions/checkout@v4

      - name: Docker metadata
        id: docker_meta
        uses: docker/metadata-action@v5
        with:
          images: ghcr.io/pterodactyl/panel
          flavor: |
            latest=false
          tags: |
            type=raw,value=latest,enable=${{ github.event_name == 'release' && github.event.action == 'published' && github.event.release.prerelease == false }}
            type=ref,event=tag
            type=ref,event=branch

      - name: Setup QEMU
        uses: docker/setup-qemu-action@v3

      - name: Setup Docker buildx
        uses: docker/setup-buildx-action@v3

      - name: Login to GitHub Container Registry
        uses: docker/login-action@v3
        if: "github.event_name != 'pull_request'"
        with:
          registry: ghcr.io
          username: ${{ github.repository_owner }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Update version
        if: "github.event_name == 'release' && github.event.action == 'published'"
        env:
          REF: ${{ github.event.release.tag_name }}
        run: |
          sed -i "s/    'version' => 'canary',/    'version' => '${REF:1}',/" config/app.php

      - name: Build and Push
        uses: docker/build-push-action@v6
        with:
          context: .
          file: ./Dockerfile
          push: ${{ github.event_name != 'pull_request' }}
          platforms: linux/amd64,linux/arm64
          labels: ${{ steps.docker_meta.outputs.labels }}
          tags: ${{ steps.docker_meta.outputs.tags }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
