# ADR-0016: AWS serverless deployment

- Status: Accepted
- Date: 2026-06-06

## Context

`PROJECT_NAME` needs a repeatable AWS deployment model for the existing application. The deployment must support isolated `preview` and `live` environments in one AWS account, and must allow additional isolated preview environments to be created in the future when required. It must serve the frontend through CloudFront and S3, run the backend on AWS Lambda with Bref, use Aurora Serverless for persistence, and deploy through GitHub Actions using AWS CDK.

## Decision

We will deploy `PROJECT_NAME` to one AWS account with separate `preview` and `live` resources managed by AWS CDK. The CDK environment model will not be limited to only those two names: it must support additional preview-style environments when required, such as temporary or named preview deployments.

Each environment will have separate CDK stacks for network, database, backend, and frontend resources. Stack and resource names will include the environment name, for example `PROJECT_SLUG-preview-backend` and `PROJECT_SLUG-live-backend`. Future preview environments must follow the same naming pattern with a unique environment name, for example `PROJECT_SLUG-preview-x-backend`.

The frontend will be built in GitHub Actions, uploaded to an environment-specific private S3 bucket, and served through an environment-specific CloudFront distribution using Origin Access Control.

The backend will run as a Bref PHP Lambda inside a VPC so it can reach Aurora. It will be exposed through a Lambda Function URL, with CloudFront routing API traffic to that origin. CloudFront will send a secret origin header, and the backend will reject requests that do not include the expected secret. Dynamic API paths will not be cached by CloudFront unless explicitly configured later.

The database will use Aurora Serverless v2 with one writer and no reader. Each environment will have a separate Aurora cluster. `preview` will use cost-optimised settings. `live` will use deletion protection and retained backups or snapshots.

Configuration values and secrets will be stored in AWS Systems Manager Parameter Store under environment-scoped paths such as `/PROJECT_SLUG/preview/...` and `/PROJECT_SLUG/live/...`. Sensitive values must use SecureString parameters. Future preview environments must use their own scoped parameter paths, for example `/PROJECT_SLUG/preview-x/...`.

GitHub Actions will deploy through AWS OIDC rather than static AWS access keys. The account will contain separate deployment roles for `preview` and `live`, and may contain additional scoped deployment roles for future preview environments. The `preview` workflow may deploy from the main branch. Additional preview environments may deploy from explicit workflow inputs, branches, or pull request workflows when approved by the team. The `live` workflow must use GitHub Environment protection, such as manual approval or a release trigger.

## Consequences

This keeps deployment simple and cost-conscious while preserving environment separation through resource naming, tags, IAM scoping, separate parameter paths, and separate stacks. It also keeps the deployment model extensible if the team later needs more than one preview environment.

Using one AWS account reduces operational overhead but provides weaker blast-radius isolation than separate accounts. IAM policies, deletion protection, environment-scoped resources, and GitHub Environment controls must be maintained carefully. Additional preview environments increase resource count and cost, so they should be created intentionally and removed when no longer needed.

Using Lambda Function URLs behind CloudFront removes API Gateway cost and complexity, but shifts request protection, throttling, validation, and some observability concerns into CloudFront, Lambda, and application code. The origin secret check is mandatory because Lambda Function URLs with CloudFront use `AuthType.NONE`.

Using Aurora Serverless v2 with a single writer reduces cost, but does not provide the same availability posture as a multi-reader or multi-account design. Database maintenance or writer failure may cause downtime.

Lambda-to-Aurora connectivity may create connection pressure during traffic spikes. The implementation must control database connection behaviour and should consider RDS Proxy if concurrency becomes a risk.

Database migrations must run as an explicit, observable deployment step in GitHub Actions or a dedicated one-off Lambda path. They must not run implicitly on every application request.
