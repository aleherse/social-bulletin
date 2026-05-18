FROM nginx:stable

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl libnss3-tools \
    && ARCH="$(dpkg --print-architecture)" \
    && curl -fsSL "https://dl.filippo.io/mkcert/latest?for=linux/${ARCH}" -o /usr/local/bin/mkcert \
    && chmod +x /usr/local/bin/mkcert \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY entrypoint.sh /usr/local/bin/certs-entrypoint.sh
RUN chmod +x /usr/local/bin/certs-entrypoint.sh

ENTRYPOINT ["certs-entrypoint.sh"]
CMD ["nginx", "-g", "daemon off;"]
