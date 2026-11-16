#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

if [[ -e public/hot ]]; then
    printf '%s\n' 'Refusing production build: public/hot exists. Stop Vite; remove the marker only if the dev server is stopped, then retry.' >&2
    exit 1
fi

npm ci
npm run build

node --input-type=module <<'NODE'
import { existsSync, readFileSync } from 'node:fs';
import { join } from 'node:path';

const manifest = JSON.parse(readFileSync('public/build/manifest.json', 'utf8'));
const requiredEntries = ['resources/css/app.css', 'resources/js/app.js'];

for (const entry of requiredEntries) {
    const asset = manifest[entry]?.file;

    if (!asset || !existsSync(join('public/build', asset))) {
        throw new Error(`Build output is missing a valid asset for ${entry}.`);
    }
}

if (existsSync('public/hot')) {
    throw new Error('Do not deploy while public/hot exists.');
}

const cssPath = join('public/build', manifest['resources/css/app.css'].file);
const css = readFileSync(cssPath, 'utf8');

for (const selector of ['.bg-ink-950', '.home-header']) {
    if (!css.includes(selector)) {
        throw new Error(`Tailwind output is missing expected selector ${selector}.`);
    }
}

console.log('Build verified: manifest/assets exist, Tailwind selectors are present, and public/hot is absent.');
NODE
