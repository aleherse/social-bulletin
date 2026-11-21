# ADR-0016: AWS serverless deployment

- Status: Accepted
- Date: 2026-06-12

## Context

The project needs repeatable cloud deployment for the frontend, API, database, realtime push, certificates, DNS,
and environment configuration.
Traffic is small and bursty, so the platform should cost close to nothing when idle
and need no servers to patch.

## Decision

### Environments and tooling

- Two separate environments SHALL exist, `live` and `preview`.
  They share one database cluster but never a database.
- AWS CDK SHALL be the deployment source of truth.
- Deployment SHALL be triggered from a GitHub action on merge into `live`.
- Every stack SHALL deploy to `eu-west-1` (Ireland),
  except the ACM certificates, which CloudFront and the AppSync custom domain only accept from `us-east-1`.
  Those live in a per-environment `certificates` stack there, referenced across regions.
- CDK SHALL manage the Route53 aliases and ACM certificates for the environment hostnames.

### Frontend and map data

- The frontend SHALL live in a private S3 bucket behind CloudFront at `LIVE_FRONT_URL`.
- The place lookup's data file SHALL live in a separate private map-data S3 bucket.
  The manual map-data workflow uploads it, not the frontend deploy,
  and the console Lambda's import reads it through a presigned URL.
  CloudFront does not serve it
  ([ADR-0024](ADR-0024-use-openstreetmap-tiles-and-self-hosted-place-lookup.md)).

### API

- The API SHALL run as a Bref PHP-FPM Lambda behind CloudFront at `LIVE_API_URL`.
- The Function URL SHALL use `AWS_IAM` auth, with CloudFront signing origin requests through origin access control,
  so the API is reachable only through CloudFront.
- Console commands SHALL run through a Bref console Lambda,
  so production migrations can be invoked after deploy.
- Both Lambdas SHALL be declared with the `@bref.sh/constructs` constructs,
  so the Bref layers and PHP version are upgraded through that package.
- The deploy workflow SHALL install production dependencies and warm the production cache
  (`cache:warmup --env=prod`) before synth.
  The warmed `var/cache/prod` SHALL ship in the Lambda package,
  because the Lambda filesystem is read-only and the container cannot be compiled at runtime.

### Realtime

- Realtime push SHALL be an AWS AppSync Events API at `LIVE_REALTIME_URL`, in its own CDK stack
  ([ADR-0024](ADR-0024-adopt-appsync-events-and-asyncapi-for-realtime-push.md)).

### Database

- The database SHALL be one Aurora Serverless v2 cluster with one writer and no reader, using cost-optimised settings.
- The cluster SHALL be shared by both environments,
  each with its own PostgreSQL database (`bulletin_live`, `bulletin_preview`).
- It SHALL scale to zero capacity after five idle minutes,
  so the first request after a pause waits for it to resume.
- It SHALL live in its own `social-bulletin-database` stack and be retained on deletion.
- Each environment SHALL connect as its own PostgreSQL role that owns only its own database.
  The roles and the `preview` database are created once by hand with the master credentials, never by the deployment
  (`docs/engineering/infrastructure/database-bootstrap.md`).

### Configuration and secrets

- Configuration and secrets SHALL use AWS Systems Manager Parameter Store under environment-scoped paths.
- Secrets SHALL reach the functions as `bref-ssm:<path>` environment variables,
  which the Bref secrets loader resolves with `ssm:GetParameters` when the function starts.
  CDK stores only the paths, never the values.
  The JWT key pair is passed as key text, not as packaged files
  (`docs/engineering/infrastructure/application-secrets.md`).

## Consequences

- Both `eu-west-1` and `us-east-1` must be CDK-bootstrapped.
- Frontend assets are served from S3 through CloudFront.
- Map tiles come from OpenStreetMap's tile servers
  ([ADR-0024](ADR-0024-use-openstreetmap-tiles-and-self-hosted-place-lookup.md));
  only the places file lives in AWS.
- The API runs on Bref PHP-FPM Lambda through CloudFront and cannot be called directly on its Function URL.
- Production console tasks run through a Bref console Lambda.
- The package must contain the warmed production cache,
  so the deploy workflow's warm-up step is mandatory and every code or configuration change needs a fresh build.
- Aurora Serverless v2 provides managed relational storage.
  A paused cluster adds roughly 15 seconds to the first request after a quiet spell.
- Parameter Store values are resolved once per cold start,
  so a changed parameter takes effect only on the next cold start or deploy.
- `live` and `preview` must stay isolated, except for the shared cluster:
  each environment's role can reach only its own database,
  and only the one-off bootstrap uses the master credentials.
- AWS infrastructure, IAM, migrations, and cost settings need ongoing maintenance.
