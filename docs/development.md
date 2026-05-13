# Development

## Local API Host

Map `api.bulletin.local` to `127.0.0.1` in your host DNS configuration before using the nginx entrypoint:

```text
127.0.0.1 api.bulletin.local
```

The health check endpoint is available at `http://api.bulletin.local/health` after `make up`.

## Local Database

`make up` starts PostgreSQL 18 on `localhost:5432` with these local-only credentials:

```text
database: social_bulletin
user: social_bulletin
password: social_bulletin
```
