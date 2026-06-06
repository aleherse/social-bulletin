FROM mcr.microsoft.com/playwright:v1.52.0-jammy

RUN apt-get update \
    && apt-get install -y --no-install-recommends python3-pip postgresql-client \
    && pip3 install --break-system-packages DSLR \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /workspace/apps/web
