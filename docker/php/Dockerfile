FROM php:8.3-cli

# unzip — для распаковки пакетов composer'ом, git — для source-фоллбека
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip git \
    && rm -rf /var/lib/apt/lists/*

# composer берём из официального образа, чтобы не ставить его в систему
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
