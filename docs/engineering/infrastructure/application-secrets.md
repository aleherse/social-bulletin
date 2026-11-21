# Application secrets

The API reads its secrets from Parameter Store at cold start:
the Lambdas carry only `bref-ssm:<path>` references,
which the Bref secrets loader resolves
([ADR-0016](../../decisions/ADR-0016-aws-serverless-deployment.md)).
CloudFormation cannot create `SecureString` parameters,
so you create the ones below by hand, once per environment, before the first request.
The Lambdas can already read everything under `/social-bulletin/<env>/`.

| Parameter                                 | Type         | Created by                                        |
|-------------------------------------------|--------------|---------------------------------------------------|
| `/social-bulletin/<env>/database-url`     | String       | `social-bulletin-database` stack                  |
| `/social-bulletin/<env>/app-secret`       | SecureString | You: a random string                              |
| `/social-bulletin/<env>/jwt-passphrase`   | SecureString | You: the passphrase of the private key            |
| `/social-bulletin/<env>/jwt-private-key`  | SecureString | You: the encrypted PEM private key                |
| `/social-bulletin/<env>/jwt-public-key`   | String       | You: the PEM public key (also read by the realtime authorizer) |

## One-off steps

Run once for `preview` and once for `live`, changing `ENV`.
Use a different key pair per environment.

```bash
export AWS_REGION=eu-west-1 ENV=preview
dir=$(mktemp -d) && umask 077
passphrase=$(openssl rand -hex 32)

openssl genpkey -algorithm RSA -pkeyopt rsa_keygen_bits:3072 -aes256 -pass "pass:$passphrase" -out "$dir/private.pem"
openssl pkey -in "$dir/private.pem" -passin "pass:$passphrase" -pubout -out "$dir/public.pem"

put() { aws ssm put-parameter --name "/social-bulletin/$ENV/$1" --type "$2" --value "$3" --overwrite >/dev/null; }
put app-secret SecureString "$(openssl rand -hex 32)"
put jwt-passphrase SecureString "$passphrase"
put jwt-private-key SecureString "file://$dir/private.pem"
put jwt-public-key String "file://$dir/public.pem"

rm -rf "$dir"
```

Parameters are limited to 4 KB, which the 3072-bit encrypted key stays under.
Rotating the key pair invalidates every issued token:
the realtime authorizer and the API both read `jwt-public-key`,
so update all four JWT values together, then redeploy or wait for the Lambdas to cold start.

## Non-secret settings

`DEFAULT_URI`, `CORS_ALLOW_ORIGIN` and the realtime URLs are set by the API stack from the environment's hostnames;
`AWS_REGION` comes from Lambda itself.
