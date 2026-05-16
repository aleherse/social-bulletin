# Development

## Local Hosts

Map local application hosts to `127.0.0.1` in your host DNS configuration before using the nginx entrypoints:

```text
127.0.0.1 api.bulletin.local
127.0.0.1 app.bulletin.local
```

The health check endpoint is available at `http://api.bulletin.local/health` after `make up`.

The web application is available at `http://app.bulletin.local` after `make init` and `make up`.

The web build uses `VITE_API_BASE_URL` to choose the API base URL and defaults to `http://api.bulletin.local` through Docker Compose. The API uses `WEB_ALLOWED_ORIGIN` for CORS and defaults to `^https?://app\.bulletin\.local(:[0-9]+)?$`.

## Local Database

`make up` starts PostgreSQL 18 on `localhost:5432` with these local-only credentials:

```text
database: social_bulletin
user: social_bulletin
password: social_bulletin
```
