# Matchday

Платформа для аматорських футбольних ліг і турнірів: календар, таблиця, живий рахунок і статистика сезону.

Організатор збирає турнір і генерує календар. Капітан веде заявку своєї команди. Суддя з телефона додає голи, картки й заміни — публічна сторінка матчу оновлює рахунок через WebSocket. Після фінального свистка таблиця перераховується у черзі.

![Головна](docs/screenshots/home.png)

![Турнір](docs/screenshots/tournament.png)

![Матч](docs/screenshots/match.png)

Знімки екрана кладуться в `docs/screenshots/`.

## Стек

Проєкт зібрано на **Laravel 12** і PHP 8.3. Laravel 11 на момент створення блокувався Composer через незакриті security advisories (виправлення є лише в 12.x), тому каркас — Laravel 12 із тим самим набором пакетів: Inertia.js, Vue 3, Tailwind CSS, Laravel Breeze, Sanctum, Reverb, Echo, Pest і Sail (MySQL 8.4 + Redis).

Модель матчу називається `Game` і лежить у таблиці `matches`: у PHP 8 `match` — зарезервоване слово.

## Запуск через Sail

Потрібні Docker і Composer.

```bash
cp .env.example .env
composer install
php artisan key:generate
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
./vendor/bin/sail artisan reverb:start
./vendor/bin/sail artisan queue:work
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

Сайт: [http://localhost](http://localhost). Reverb слухає порт `8080`. Redis тримає кеш, сесії й черги. Подія `MatchFinished` ставить у чергу перерахунок таблиці, тож `queue:work` має працювати разом із сайтом.

Тести не потребують Docker: вони йдуть на SQLite в пам’яті.

```bash
php artisan test
```

## Демо-вхід

Пароль для всіх акаунтів: `password`.

| Роль | Email |
| --- | --- |
| Адміністратор | `admin@matchday.test` |
| Суддя | `referee@matchday.test` |
| Другий суддя | `referee2@matchday.test` |
| Капітан «Буревію» | `captain@matchday.test` |
| Капітани інших клубів | `captain2@matchday.test` … `captain10@matchday.test` |

Сідер створює лігу «Matchday Ліга» на сезон 2026/27: 10 команд по 15 гравців, два кола, зіграні тури 1–9 і один live-матч у 10-му турі.

## API

Публічні `GET` не потребують токена. Префікс — `/api`. Турнір адресується slug-ом.

Отримати токен:

```bash
curl -X POST http://localhost/api/login \
  -H 'Accept: application/json' \
  -d 'email=admin@matchday.test' \
  -d 'password=password' \
  -d 'device_name=laptop'
```

Відповідь: `{ "token": "...", "user": { "id", "name", "email", "role" } }`. Невірні дані — `422`.

Далі передавайте `Authorization: Bearer {token}`.

| Метод | Шлях | Доступ | Що повертає |
| --- | --- | --- | --- |
| `POST` | `/api/login` | гість | токен Sanctum і користувача |
| `POST` | `/api/logout` | токен | відкликає поточний токен |
| `GET` | `/api/user` | токен | поточного користувача |
| `GET` | `/api/tournaments/{slug}/standings` | публічний | таблиці: І, В, Н, П, м’ячі, різниця, очки, форма |
| `GET` | `/api/tournaments/{slug}/fixtures?round=` | публічний | матчі; `round` необов’язковий |
| `GET` | `/api/tournaments/{slug}/top-scorers` | публічний | голи, пенальті, асисти |
| `GET` | `/api/matches/{id}` | публічний | рахунок, хронологія, склади |

Приклад:

```bash
curl http://localhost/api/tournaments/matchday-liga/standings
curl http://localhost/api/tournaments/matchday-liga/fixtures?round=10
curl http://localhost/api/matches/1
```
