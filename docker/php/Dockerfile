FROM php:8.3-cli

# composer берём из официального образа, чтобы не ставить его в систему
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
