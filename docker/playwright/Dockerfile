FROM mcr.microsoft.com/playwright:v1.60.0-jammy

RUN apt-get update \
    && apt-get install -y --no-install-recommends python3-pip postgresql-client \
    && npm install --global npm@latest \
    && pip3 install DSLR psycopg2-binary \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace/apps/web
