#!/usr/bin/env sh
set -eu

cd "$(dirname "$0")"

if [ ! -f .env ]; then
    cp .env.example .env
    echo ".env created from .env.example"
fi

if grep -qE '^APP_KEY=$' .env; then
    KEY="base64:$(docker run --rm php:8.4-cli-alpine php -r 'echo base64_encode(random_bytes(32));')"
    sed -i.bak "s|^APP_KEY=$|APP_KEY=$KEY|" .env && rm -f .env.bak
    echo "APP_KEY generated"
fi

docker compose up -d --build --wait

PORT=$(grep -E '^APP_PORT=' .env | cut -d= -f2)
echo ""
echo "  App:      http://localhost:${PORT:-8080}  (admin@example.com / password)"
echo "  Swagger:  http://localhost:${PORT:-8080}/docs"
echo "  RabbitMQ: http://localhost:15672  (guest / guest)"
