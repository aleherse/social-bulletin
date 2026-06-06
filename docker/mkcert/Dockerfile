FROM golang:1.25-alpine

RUN apk add --no-cache nss-tools \
    && go install filippo.io/mkcert@latest \
    && cp /go/bin/mkcert /usr/local/bin/mkcert

WORKDIR /workspace
