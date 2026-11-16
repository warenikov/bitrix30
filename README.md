# bitrix30

Современный PHP-фреймворк с битриксовыми концепциями: компоненты, наследуемые сайты, решения, контент-модель. Коротко — «Laravel, только БУС».

Вдохновлён [framework3-prototype](https://github.com/bitrix24/framework3-prototype) — прототипом нового ядра, который команда Битрикса показала в 2022 году и забросила. Код оттуда не используется (у прототипа нет лицензии) — только идеи, переосмысленные и написанные с нуля.

> **Дисклеймер:** проект не аффилирован с ООО «1С-Битрикс». «Битрикс» — товарный знак соответствующего правообладателя.

## Статус

Ранняя разработка, этап «MVP визитка»: HTTP-ядро (PSR-7/15/17), DI, роутинг, Twig, компоненты, сайты с наследованием, layouts, Monolog и консоль. Доступен read-only Admin v0 с модулями и диагностикой роутов.

Проект развивается build-in-public: подсистемы разбираются в статьях по мере написания. Документация для пользователей появится, когда будет что документировать.

## Требования

PHP ≥ 8.3, Composer.

## Запуск в Docker

```bash
docker compose run --rm php composer install
docker compose up -d
```

Сайт: `http://localhost:8030`. Read-only админка: `http://localhost:8030/admin`.

Для доступа к админке задайте логин и хеш пароля перед запуском:

```bash
export ADMIN_EMAIL='admin@example.com'
export ADMIN_PASSWORD_HASH='<результат password_hash>'
docker compose up -d --force-recreate
```

Сгенерировать хеш можно интерактивно: `docker compose run --rm php php -r 'echo password_hash(readline("Пароль: "), PASSWORD_BCRYPT), PHP_EOL;'`; открытый пароль в конфиги не записывается. Без `ADMIN_PASSWORD_HASH` админка отвечает 503. Для публичного окружения Basic Auth допускается только по HTTPS; ограничение перебора пароля — задача reverse proxy.

## Лицензия

[MIT](LICENSE)
