# ADR-0022: the tools that build the map data, used only by `make map-data`.
FROM public.ecr.aws/docker/library/debian:bookworm-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl jq osmium-tool \
    && rm -rf /var/lib/apt/lists/*

ENV HOME=/tmp
WORKDIR /app
