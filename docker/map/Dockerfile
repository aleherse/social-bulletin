# ADR-0022: the tools that build the map data, used only by `make map-data`.
FROM public.ecr.aws/docker/library/debian:bookworm-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl jq osmium-tool \
    && rm -rf /var/lib/apt/lists/*

# Pinned go-pmtiles release; checksums from its GitHub release assets.
ARG PMTILES_VERSION=1.31.2
ARG TARGETARCH
RUN case "$TARGETARCH" in \
        amd64) asset=Linux_x86_64; sha256=3ed7dbf4ec2e6dfe5e25b6f70d1ffc932729f93c86db353bf514dd71010a312f ;; \
        arm64) asset=Linux_arm64; sha256=f8bd47e7ea866863489cad588fbaf2f31f42e5821f7a03f009b3769f05801cb1 ;; \
        *) echo "Unsupported architecture: $TARGETARCH" >&2; exit 1 ;; \
    esac \
    && curl --fail --location --silent --show-error --output /tmp/pmtiles.tar.gz \
        "https://github.com/protomaps/go-pmtiles/releases/download/v${PMTILES_VERSION}/go-pmtiles_${PMTILES_VERSION}_${asset}.tar.gz" \
    && echo "${sha256}  /tmp/pmtiles.tar.gz" | sha256sum -c - \
    && tar -xzf /tmp/pmtiles.tar.gz -C /usr/local/bin pmtiles \
    && rm /tmp/pmtiles.tar.gz

ENV HOME=/tmp
WORKDIR /app
