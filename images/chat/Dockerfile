FROM node:24-alpine AS builder

WORKDIR /tmp/chat

# To be eventually replaced by clone of a build release
ARG REPO_BRANCH="master"
ENV REPO_URL="https://gitlab.hallowelt.com/BlueSpice/webservice-chat.git"
ADD "$REPO_URL#$REPO_BRANCH" /tmp/chat/
RUN find . -type d -name '.git' | xargs rm -rf {} \;
RUN npm install

FROM node:24-alpine

RUN apk add curl

WORKDIR /app

COPY root-fs/* ./
COPY --from=builder /tmp/chat ./chat
RUN chmod +x ./chat/start.sh

HEALTHCHECK --start-period=10s --retries=3 --interval=1m --timeout=20s \
    CMD curl --silent --output /dev/null http://localhost:3002 || exit 1

CMD ["/app/bin/entrypoint"]
