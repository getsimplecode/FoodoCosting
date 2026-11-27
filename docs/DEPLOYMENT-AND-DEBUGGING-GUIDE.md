# FoodoCosting: Run, Deploy, and Debug Guide

This guide covers the current local setup, Render deployment, common backend and frontend errors, and a suggested learning path.

## How the app is deployed

The production flow is:

1. The source code is stored in the GitHub repository, on the `main` branch.
2. Render builds the `Dockerfile` after a push to `main`.
3. The Docker build uses Node 22 to build the Vite assets and PHP 8.3 with Apache to run Laravel.
4. The container runs `php artisan migrate --force`, then starts Apache on port `10000`.
5. Laravel connects to the MySQL-compatible TiDB database.

Important project files:

- [`Dockerfile`](../Dockerfile): builds the frontend and PHP image, runs migrations, and starts Apache.
- [`bootstrap/app.php`](../bootstrap/app.php): tells Laravel to trust Render's forwarded HTTPS headers.
- [`routes/auth.php`](../routes/auth.php): login and account routes. Public registration has been disabled.
- [`database/migrations`](../database/migrations): database table definitions and changes.
- [`resources/css/mainlayout.css`](../resources/css/mainlayout.css): main application styling.
- [`vite.config.js`](../vite.config.js): frontend asset build configuration.

## Run the app locally on Windows

Install PHP 8.2 or newer, Composer, Node.js/npm, and a local database such as Laragon MySQL or SQLite.

Open PowerShell in the project folder:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
npm install
```

Configure the local database in `.env`. Do not copy production database credentials into a file that could be committed. Generate an application key only if `APP_KEY` is empty:

```powershell
php artisan key:generate
```

If using SQLite locally and the database file does not exist, create it first:

```powershell
if (-not (Test-Path database\database.sqlite)) {
    New-Item -ItemType File database\database.sqlite | Out-Null
}
```

If using local MySQL instead, configure its connection in `.env` and create the database first.

Create local tables:

```powershell
php artisan migrate
```

Run the Laravel server in one terminal and Vite in another:

```powershell
php artisan serve
```

```powershell
npm run dev
```

Open the local URL printed by `php artisan serve`. For a production-style asset build, run:

```powershell
npm run build
```

To check that the Docker image builds locally:

```powershell
docker build -t foodocosting .
```

This checks the image build, not the live TiDB credentials or Render environment. Do not copy production secrets into a local image build.

Run tests with:

```powershell
php artisan test
```

Do not run local migration commands while `.env` points to the production TiDB database unless you intentionally mean to change production.

## Render environment settings

Set these on the **FoodoCosting web service** in Render's Environment page. An environment group only applies if it is linked to the service.

| Key | Value |
| --- | --- |
| `APP_NAME` | `FoodoCosting` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | A private Laravel key |
| `APP_URL` | `https://foodocosting.onrender.com` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `error` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | TiDB host from its Connect page; hostname only |
| `DB_PORT` | TiDB port, currently `4000` |
| `DB_DATABASE` | `foodocostingdb` |
| `DB_USERNAME` | TiDB database username |
| `DB_PASSWORD` | TiDB database password |
| `MYSQL_ATTR_SSL_CA` | `/etc/ssl/certs/ca-certificates.crt` |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `SESSION_HTTP_ONLY` | `true` |
| `SESSION_SAME_SITE` | `lax` |

Never put real values in documentation, GitHub, screenshots, or chat. The local `.env` is excluded from Docker builds by `.dockerignore`; keep it out of Git as well. If a password or application key is exposed publicly, rotate it.

The app trusts forwarded client-IP and HTTPS headers only from the immediate network peer, and does not trust forwarded host headers. The accepted host and generated production URLs come from `APP_URL`. Keep the Render service behind its proxy; do not expose the app container directly.

## Deploy changes

From the project folder, review the files before staging:

```powershell
git status --short
```

Stage only the files you intend to deploy; do not use `git add .` if you are unsure what it will include. Then commit and push:

```powershell
git add path\to\changed-file
git commit -m "Describe the change"
git push origin main
```

In Render, open **FoodoCosting → Events/Deploys** and wait for the newest deployment to show **Live**. If it fails, inspect the logs for that deployment, not an older one.

## Debug backend errors

1. Open **Render → FoodoCosting → Logs**.
2. Reproduce the problem once, then inspect the newest Laravel line beginning with `[production.ERROR]`.
3. Use the first exception message to identify the cause. The remaining stack trace usually shows where the error passed through Laravel.
4. Keep `APP_DEBUG=false` in production. Share only the relevant error line, with credentials and tokens removed.

Common messages seen during this deployment:

| Error | Meaning / next check |
| --- | --- |
| `Connection refused` at `127.0.0.1:3306` | The app is trying to use a local database. Use the TiDB host in Render instead. |
| `Connections using insecure transport are prohibited` | TiDB requires TLS. Check `MYSQL_ATTR_SSL_CA` on the Render service and its exact path. |
| `1045 Access denied` | TiDB rejected the database username or password. Check the credentials for the same cluster; never share them. |
| `Table ... sessions doesn't exist` | Laravel migrations have not created the session table in the selected database. Check the startup migration output and database name. |
| `Table ... sessions already exists` during migration | The table exists but Laravel's migration history may not match. Verify the table schema and migration record before changing either; do not drop production tables as a guess. |
| Generic `500 Server Error` | The browser message is not enough. Find the newest `[production.ERROR]` line in Render logs. |
| Apache `AH00558` ServerName notice | Usually informational; it is not the cause of Laravel database errors. |

Laravel migrations are the source of truth for database structure. The database/schema itself must exist in TiDB first; migrations create the tables inside it. The `sessions` table migration was added because this app uses database-backed sessions.

## Debug frontend problems

1. Hard-refresh the page with **Ctrl+F5**.
2. Open browser Developer Tools with **F12**, then check **Console** and **Network**.
3. Confirm the CSS and JavaScript files under `/build/assets/` load with status `200`.
4. On the HTTPS Render site, asset and form URLs should also start with `https://`, not `http://`. An HTTP stylesheet on an HTTPS page is mixed content and browsers may block it.
5. If Vite assets are missing after a code change, check the Docker build logs for `npm run build` and inspect `public/build/manifest.json`.

The app's `bootstrap/app.php` trusts Render's forwarded HTTPS headers, and `APP_URL` should use the HTTPS Render URL. If the page still looks unstyled, check the actual generated stylesheet URL before changing CSS.

## Current account behavior

Public `GET /register` and `POST /register` routes are disabled in [`routes/auth.php`](../routes/auth.php). After deployment, `/register` should return `404`. This blocks new public signups; it does not delete accounts that already exist.

Dashboard and application data routes require a verified email address. Before deploying this behavior, check existing accounts for a null `email_verified_at`; those users must verify their email before using those routes. Changing an account email sends a new verification link and pauses access to verified routes until it is confirmed.

## Suggested learning path

1. **HTML and CSS:** forms, layout, responsive design, browser Developer Tools.
2. **JavaScript and Vite:** how CSS/JavaScript are bundled and served from `public/build`.
3. **PHP and Laravel:** routes, controllers, Blade views, middleware, authentication, and validation.
4. **SQL and migrations:** tables, queries, indexes, Laravel migrations, and why production data must be backed up.
5. **Git and GitHub:** `status`, staging selected files, commits, branches, pushes, and deployment history.
6. **Docker:** images, Dockerfiles, build stages, ports, environment variables, and container logs.
7. **Render and production operations:** environment settings, deploy logs, HTTPS/proxy behavior, backups, and access control.

Learn in small steps: make a change locally, run the focused test, inspect `git status`, commit only the intended files, push, then verify the Render deployment and browser behavior.
