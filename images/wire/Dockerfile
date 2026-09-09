FROM node:24-alpine AS builder

WORKDIR /tmp/wire

ARG REPO_BRANCH="1.0.x"
# To be eventually replaced by clone of a build release
ENV REPO_URL="https://github.com/hallowelt/webservice-wire.git"
ADD "$REPO_URL#$REPO_BRANCH" /tmp/wire/
RUN find . -type d -name '.git' | xargs rm -rf {} \;
RUN npm install

FROM node:24-alpine

WORKDIR /app

COPY root-fs/* ./
COPY --from=builder /tmp/wire ./wire
USER 1003

CMD ["/app/bin/entrypoint"]
