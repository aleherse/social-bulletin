FROM node:22-bookworm

RUN apt-get update && apt-get install -y gosu && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY docker/node/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["npm", "run", "dev", "--prefix", "apps/web", "--", "--host", "0.0.0.0", "--port", "3000"]
