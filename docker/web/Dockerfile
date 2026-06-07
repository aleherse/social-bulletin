FROM node:24-alpine

RUN npm install --global npm@latest

WORKDIR /workspace/apps/web

CMD ["npm","run","dev", "--", "--host", "0.0.0.0"]
