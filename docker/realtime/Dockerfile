FROM public.ecr.aws/docker/library/node:22-alpine

# The sources and their dependencies come from the bind-mounted repository (`make deps` installs them),
# like the other node services, so there is nothing to build here.
USER node
WORKDIR /app/docker/realtime

CMD ["node", "server.mjs"]
