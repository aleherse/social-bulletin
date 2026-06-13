FROM node:lts-bookworm

ARG UID=1000
ARG GID=1000

RUN groupmod -g ${GID} node && usermod -u ${UID} -g ${GID} node

WORKDIR /var/www/app

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["npm", "run", "dev"]
