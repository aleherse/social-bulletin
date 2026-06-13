FROM node:22-bookworm-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        fonts-freefont-ttf \
        fonts-liberation \
        fonts-noto-color-emoji \
        git \
        gosu \
        libasound2 \
        libatk-bridge2.0-0 \
        libatk1.0-0 \
        libatspi2.0-0 \
        libcairo2 \
        libcups2 \
        libdbus-1-3 \
        libdrm2 \
        libfontconfig1 \
        libfreetype6 \
        libgbm1 \
        libglib2.0-0 \
        libnspr4 \
        libnss3 \
        libpango-1.0-0 \
        libx11-6 \
        libxcb1 \
        libxcomposite1 \
        libxdamage1 \
        libxext6 \
        libxfixes3 \
        libxkbcommon0 \
        libxrandr2 \
        xfonts-scalable \
        xvfb \
    && mkdir -p /workspace /home/node/.npm \
    && chown -R node:node /workspace /home/node/.npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY docker/node/entrypoint.sh /usr/local/bin/socialbulletin-node-entrypoint
RUN chmod +x /usr/local/bin/socialbulletin-node-entrypoint

WORKDIR /workspace/apps/web
ENTRYPOINT ["socialbulletin-node-entrypoint"]
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
