# Database bootstrap

Both environments share one Aurora cluster ([ADR-0016](../../decisions/ADR-0016-aws-serverless-deployment.md)).
CloudFormation cannot create PostgreSQL roles or databases,
so after the first deploy of `social-bulletin-database` you run this once, by hand.
The deployment never does it, and the GitHub workflow does not either.

## What the deployment provides

- The cluster, with `bulletin_live` as its default database, owned by the master user.
- One Secrets Manager secret per environment, `social-bulletin/<env>/database`,
  holding the role name and a generated password.
- The `database-url` SSM parameters, which already point at those roles.
- The master secret's ARN, as the `MasterSecretArn` output of `social-bulletin-database`.

## One-off steps

The cluster is publicly reachable and wakes from a pause in about 15 seconds,
so run these from any machine with `psql` and AWS credentials.

```bash
export AWS_REGION=eu-west-1
HOST=$(aws ssm get-parameter --name /social-bulletin/live/database-host --query Parameter.Value --output text)
MASTER_ARN=$(aws cloudformation describe-stacks --stack-name social-bulletin-database \
  --query "Stacks[0].Outputs[?OutputKey=='MasterSecretArn'].OutputValue" --output text)
MASTER_PASSWORD=$(aws secretsmanager get-secret-value --secret-id "$MASTER_ARN" \
  --query SecretString --output text | python3 -c 'import json,sys; print(json.load(sys.stdin)["password"])')
password() {
  aws secretsmanager get-secret-value --secret-id "social-bulletin/$1/database" \
    --query SecretString --output text | python3 -c 'import json,sys; print(json.load(sys.stdin)["password"])'
}

PGPASSWORD="$MASTER_PASSWORD" psql "host=$HOST user=bulletin dbname=postgres sslmode=require" \
  -v live_password="$(password live)" -v preview_password="$(password preview)" <<'SQL'
CREATE ROLE bulletin_live LOGIN PASSWORD :'live_password';
CREATE ROLE bulletin_preview LOGIN PASSWORD :'preview_password';

ALTER DATABASE bulletin_live OWNER TO bulletin_live;
CREATE DATABASE bulletin_preview OWNER bulletin_preview;

-- Each role reaches only its own database.
REVOKE CONNECT ON DATABASE bulletin_live FROM PUBLIC;
REVOKE CONNECT ON DATABASE bulletin_preview FROM PUBLIC;
GRANT CONNECT ON DATABASE bulletin_live TO bulletin_live;
GRANT CONNECT ON DATABASE bulletin_preview TO bulletin_preview;
SQL
```

Then run the migrations for each environment through its console Lambda.

## Afterwards

- Keep the master secret for administration only; no application reads it.
- Rotating an environment's password means updating its secret and redeploying the database stack,
  then running `ALTER ROLE <role> PASSWORD ...` with the new value.
- Deleting the cluster is blocked by its `RETAIN` policy; remove it by hand if you ever mean to.
