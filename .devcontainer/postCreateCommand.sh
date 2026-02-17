#!/usr/bin/env bash
set -euo pipefail

chown -R 1000:1000 /var/www/html 2>/dev/null || true

NPM_GLOBAL_DIR="$HOME/.npm-global"
mkdir -p "$NPM_GLOBAL_DIR"

npm config set prefix "$NPM_GLOBAL_DIR"

path_line='export PATH="$HOME/.npm-global/bin:$PATH"'
for profile_file in "$HOME/.profile" "$HOME/.bashrc"; do
    touch "$profile_file"
    if ! grep -qF "$path_line" "$profile_file"; then
        printf '\n# npm global bin\n%s\n' "$path_line" >> "$profile_file"
    fi
done

export PATH="$HOME/.npm-global/bin:$PATH"
npm install -g @openai/codex
curl -fsSL https://claude.ai/install.sh | bash
