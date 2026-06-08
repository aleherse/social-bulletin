# ADR-0016: AWS serverless deployment

- Status: Accepted
- Date: 2026-06-06

## Context

The project needs repeatable AWS deployment for a React frontend, Symfony PHP backend, and PostgreSQL database. It must support isolated `preview` and `live` environments in one AWS account, with room for extra preview environments later.

## Decision

Deploy `PROJECT_NAME` to one AWS account using AWS CDK, with separate `preview`, `live`, and future named preview resources.

Deployment configuration lives under the repository `infrastructure/` folder. This folder contains the AWS CDK app, environment configuration, stack definitions, and deployment support files needed to provision and update AWS resources.

Each environment has separate network, database, backend, and frontend stacks. Stack and resource names include the environment name, such as `PROJECT_SLUG-preview-backend`, `PROJECT_SLUG-live-backend`, or `PROJECT_SLUG-preview-x-backend`.

GitHub Actions builds the frontend, uploads it to an environment-specific private S3 bucket, and serves it through CloudFront using Origin Access Control.

Each environment has managed DNS in Route 53. The deployment creates or updates environment-specific alias records that point public hostnames to the environment CloudFront distribution. `live` uses the production hostname, `preview` uses a preview hostname, and future named preview environments use their own environment-scoped hostnames.

Each environment has an AWS Certificate Manager certificate for its public hostnames. Certificates used by CloudFront are issued in `us-east-1`, validated through Route 53 DNS records, and attached to the matching environment distribution. Certificate validation and DNS aliases are part of deployment, not manual setup.

The backend runs as a Bref PHP Lambda inside a VPC, exposed through a Lambda Function URL behind CloudFront. CloudFront sends a secret origin header, and the backend rejects requests without it. Dynamic API paths are not cached unless explicitly configured later.

Each environment has a separate Aurora Serverless v2 cluster with one writer and no reader. `preview` uses cost-optimised settings. `live` uses deletion protection and retained backups or snapshots.

Configuration and secrets use AWS Systems Manager Parameter Store under environment-scoped paths such as `/PROJECT_SLUG/preview/...`, `/PROJECT_SLUG/live/...`, and `/PROJECT_SLUG/preview-x/...`. Sensitive values use SecureString.

GitHub Actions deploys through AWS OIDC, not static access keys. Each environment has a scoped deployment role. `preview` may deploy from `main`; future preview environments may deploy from approved workflow inputs, branches, or pull request workflows. `live` requires GitHub Environment protection, such as manual approval or a release trigger.

## Consequences

- This keeps deployment simple and cost-conscious while preserving environment separation through stacks, names, tags, IAM scoping, DNS aliases, certificates, and parameter paths.
- Keeping deployment configuration in `infrastructure/` gives one explicit boundary for AWS configuration and avoids scattering provisioning concerns across application packages.
- Route 53 and ACM management make environment endpoints reproducible and reduce manual release steps. They also require careful hostname ownership, DNS validation, and certificate lifecycle handling for every environment.
- One AWS account reduces operational overhead but gives weaker blast-radius isolation than separate accounts. IAM policies, deletion protection, environment scoping, and GitHub Environment controls must be maintained carefully. Extra preview environments increase cost and should be removed when no longer needed.
- Lambda Function URLs behind CloudFront avoid API Gateway cost and complexity, but move request protection, throttling, validation, and some observability concerns into CloudFront, Lambda, and application code. The origin secret check is mandatory because Lambda Function URLs use `AuthType.NONE`.
- Aurora Serverless v2 with one writer reduces cost but gives lower availability than a multi-reader or multi-account design. Database maintenance or writer failure may cause downtime.
- Lambda-to-Aurora connectivity may create connection pressure during traffic spikes. The implementation must control database connection behaviour and should consider RDS Proxy if concurrency becomes a risk.
- Database migrations must run as an explicit, observable deployment step in GitHub Actions or a dedicated one-off Lambda path. They must not run implicitly on every application request.
