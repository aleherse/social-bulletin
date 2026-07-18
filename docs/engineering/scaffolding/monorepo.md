# Monorepo layout

| Path             | Purpose                                                                            |
|------------------|------------------------------------------------------------------------------------|
| `apps/api`       | Symfony HTTP layer only: controllers, security, Doctrine DBAL wiring, translations |
| `packages/core`  | framework-free domain logic, no Symfony/HTTP dependencies                          |
| `apps/web`       | React + Vite + TypeScript frontend structured with Feature-Sliced Design           |
| `infrastructure` | AWS CDK TypeScript app                                                             |
| `docker`         | container images and nginx config                                                  |
