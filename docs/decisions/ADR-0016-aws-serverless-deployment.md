# ADR-0016: AWS serverless deployment

- Status: Accepted
- Date: 2026-06-12

## Context

The project needs repeatable cloud deployment for the frontend, API, database, certificates, DNS, and environment configuration.

## Decision

- Production SHALL use AWS CDK as the deployment source of truth.
- The frontend SHALL live in a private S3 bucket behind CloudFront at `LIVE_FRONT_URL`.
- The place lookup's data file SHALL live in a separate private map-data S3 bucket,
  uploaded by the manual map-data workflow, not by the frontend deploy,
  and read by the console Lambda's import through a presigned URL;
  it is not served by CloudFront
  ([ADR-0024](ADR-0024-use-openstreetmap-tiles-and-self-hosted-place-lookup.md)).
- The API SHALL run as a Bref PHP-FPM Lambda behind CloudFront at `LIVE_API_URL`.
- Console commands SHALL run through a Bref console Lambda so production migrations can be invoked after deploy.
- CDK SHALL manage the Route53 aliases and ACM certificate for the production hostnames.
- Realtime push SHALL be an AWS AppSync Events API at `LIVE_REALTIME_URL`, in its own CDK stack (ADR-0024).
- Database SHALL use one Aurora Serverless v2 cluster with one writer and no reader, uses cost-optimised settings.
  The cluster is shared by both environments,
  each with its own PostgreSQL database (`bulletin_live`, `bulletin_preview`),
  and scales to zero capacity after five idle minutes, so the first request after a pause waits for it to resume.
  It lives in its own `social-bulletin-database` stack and is retained on deletion.
  Each environment connects as its own PostgreSQL role that owns only its own database;
  the roles and the `preview` database are created once by hand, never by the deployment
  (`docs/engineering/infrastructure/database-bootstrap.md`).
- Configuration and secrets SHALL use AWS Systems Manager Parameter Store under environment-scoped paths.
- Deployment SHALL be triggered from a GitHub action on merge into `live`.
- Two separate environments SHALL be created `live` and `preview`; they share the database cluster but never a database.

## Consequences

- AWS CDK becomes the deployment source of truth.
- Frontend assets are served from S3 through CloudFront.
- Map tiles come from OpenStreetMap's tile servers ([ADR-0024](ADR-0024-use-openstreetmap-tiles-and-self-hosted-place-lookup.md)); only the places file lives in AWS.
- The API runs on Bref PHP-FPM Lambda through CloudFront.
- Production console tasks run through a Bref console Lambda.
- Aurora Serverless v2 provides managed relational storage.
- Parameter Store holds environment-scoped configuration and secrets.
- Deployments run from GitHub Actions on merge to `live`.
- `live` and `preview` environments must stay isolated, except for the shared cluster:
  each environment's role can reach only its own database, and only the one-off bootstrap uses the master credentials.
- A paused cluster adds roughly 15 seconds to the first request after a quiet spell.
- AWS infrastructure, IAM, migrations, and cost settings need ongoing maintenance.
