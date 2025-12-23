FROM node:24-alpine AS builder

WORKDIR /tmp/wire

ARG REPO_BRANCH="1.0.x"
# To be eventually replaced by clone of a build release
ARG REPO_BRANCH="1.0.x"
ENV REPO_URL="https://gitlab.hallowelt.com/BlueSpice/webservice-wire.git"
ADD "$REPO_URL#$REPO_BRANCH" /tmp/wire/
RUN find . -type d -name '.git' | xargs rm -rf {} \;
RUN npm install

FROM node:24-alpine

WORKDIR /app

COPY root-fs/* ./
COPY --from=builder /tmp/wire ./wire
RUN chmod +x ./wire/start.sh

CMD ["/app/bin/entrypoint"]