#!/usr/bin/env bash
set -e

BASE_URL="https://raw.githubusercontent.com/Nyamort/OpenWatch/refs/heads/main/docker/production"
ENV_FILE=".env"

echo ""
echo "  OpenWatch — Setup"
echo "  ================="
echo ""

# ── Check dependencies ───────────────────────────────────────────
for cmd in docker openssl curl; do
    if ! command -v "$cmd" &>/dev/null; then
        echo "  [ERROR] '$cmd' is required but not installed."
        exit 1
    fi
done

# ── Download docker-compose.yml ──────────────────────────────────
if [ ! -f "docker-compose.yml" ]; then
    curl -fsSL "${BASE_URL}/docker-compose.yml" -o docker-compose.yml
    echo "  [✓] Downloaded docker-compose.yml"
else
    echo "  [✓] docker-compose.yml already exists — skipping"
fi

# ── Download .env.example and create .env ────────────────────────
if [ -f "$ENV_FILE" ]; then
    echo "  [!] .env already exists — skipping."
    echo "      Delete it and re-run setup.sh to start fresh."
    echo ""
else
    curl -fsSL "${BASE_URL}/.env.example" -o "$ENV_FILE"
    echo "  [✓] Created .env from template"
fi

# ── Generate APP_KEY ─────────────────────────────────────────────
if grep -q "^APP_KEY=\s*$" "$ENV_FILE"; then
    APP_KEY="base64:$(openssl rand -base64 32)"
    sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" "$ENV_FILE"
    echo "  [✓] Generated APP_KEY"
else
    echo "  [✓] APP_KEY already set — skipping"
fi

# ── DB_PASSWORD ──────────────────────────────────────────────────
if grep -q "^DB_PASSWORD=\s*$" "$ENV_FILE"; then
    echo ""
    printf "  MySQL password (leave empty to generate one): "
    read -r DB_PASSWORD_INPUT

    if [ -z "$DB_PASSWORD_INPUT" ]; then
        DB_PASSWORD="$(openssl rand -base64 18 | tr -d '/+=' | head -c 24)"
        echo "  [✓] Generated DB_PASSWORD: $DB_PASSWORD"
        echo "      (saved in .env — no need to remember it)"
    else
        DB_PASSWORD="$DB_PASSWORD_INPUT"
        echo "  [✓] DB_PASSWORD set"
    fi

    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" "$ENV_FILE"
    # Root password is an internal detail — always auto-generated
    sed -i "s|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=$(openssl rand -base64 18 | tr -d '/+=' | head -c 24)|" "$ENV_FILE"
    echo "  [✓] Generated DB_ROOT_PASSWORD"
    echo ""
else
    echo "  [✓] DB_PASSWORD already set — skipping"
fi

# ── APP_URL (required) ───────────────────────────────────────────
if grep -q "^APP_URL=https://$" "$ENV_FILE" || grep -q "^APP_URL=\s*$" "$ENV_FILE"; then
    echo ""
    while true; do
        printf "  Public URL of your instance (e.g. https://watch.example.com): "
        read -r APP_URL_INPUT
        if [ -n "$APP_URL_INPUT" ]; then
            sed -i "s|^APP_URL=.*|APP_URL=${APP_URL_INPUT}|" "$ENV_FILE"
            echo "  [✓] APP_URL set"
            break
        else
            echo "  [!] APP_URL is required."
        fi
    done
    echo ""
fi

# ── ClickHouse password (optional) ───────────────────────────────
if grep -q "^CLICKHOUSE_PASSWORD=\s*$" "$ENV_FILE"; then
    printf "  ClickHouse password (leave empty to skip): "
    read -r CLICKHOUSE_PASSWORD_INPUT

    if [ -n "$CLICKHOUSE_PASSWORD_INPUT" ]; then
        sed -i "s|^CLICKHOUSE_PASSWORD=.*|CLICKHOUSE_PASSWORD=${CLICKHOUSE_PASSWORD_INPUT}|" "$ENV_FILE"
        echo "  [✓] CLICKHOUSE_PASSWORD set"
    else
        echo "  [✓] ClickHouse password skipped (no auth)"
    fi
    echo ""
fi

# ── Mail (optional) ───────────────────────────────────────────────
echo "  Mail configuration (used for alerts and notifications)"
printf "  SMTP host (leave empty to skip): "
read -r MAIL_HOST_INPUT

if [ -n "$MAIL_HOST_INPUT" ]; then
    sed -i "s|^MAIL_MAILER=.*|MAIL_MAILER=smtp|" "$ENV_FILE"
    sed -i "s|^MAIL_HOST=.*|MAIL_HOST=${MAIL_HOST_INPUT}|" "$ENV_FILE"

    printf "  SMTP port [587]: "
    read -r MAIL_PORT_INPUT
    MAIL_PORT_INPUT="${MAIL_PORT_INPUT:-587}"
    sed -i "s|^MAIL_PORT=.*|MAIL_PORT=${MAIL_PORT_INPUT}|" "$ENV_FILE"

    printf "  SMTP username: "
    read -r MAIL_USERNAME_INPUT
    sed -i "s|^MAIL_USERNAME=.*|MAIL_USERNAME=${MAIL_USERNAME_INPUT}|" "$ENV_FILE"

    printf "  SMTP password: "
    read -rs MAIL_PASSWORD_INPUT
    echo ""
    sed -i "s|^MAIL_PASSWORD=.*|MAIL_PASSWORD=${MAIL_PASSWORD_INPUT}|" "$ENV_FILE"

    printf "  From address [hello@example.com]: "
    read -r MAIL_FROM_INPUT
    MAIL_FROM_INPUT="${MAIL_FROM_INPUT:-hello@example.com}"
    sed -i "s|^MAIL_FROM_ADDRESS=.*|MAIL_FROM_ADDRESS=${MAIL_FROM_INPUT}|" "$ENV_FILE"

    echo "  [✓] Mail configured"
else
    echo "  [✓] Mail skipped — alerts will be logged locally only"
fi
echo ""

# ── Secure the .env file ─────────────────────────────────────────
chmod 600 "$ENV_FILE"
echo "  [✓] Set .env permissions to 600"

# ── Summary ──────────────────────────────────────────────────────
echo ""
echo "  ┌─────────────────────────────────────────────────────────┐"
echo "  │  All done! Start OpenWatch with:                        │"
echo "  │                                                         │"
echo "  │    docker compose up -d                                 │"
echo "  └─────────────────────────────────────────────────────────┘"
echo ""
