FROM node:24-alpine AS builder

WORKDIR /tmp/chat

# To be eventually replaced by clone of a build release
RUN apk update && apk add --no-cache git
ARG REPO_BRANCH="master"
ARG GITLAB_TOKEN
ARG GITLAB_USERNAME="bluespice-bot"
ENV REPO_URL="https://${GITLAB_USERNAME}:${GITLAB_TOKEN}@gitlab.hallowelt.com/BlueSpice/webservice-chat.git"
RUN git clone --depth=1 -b ${REPO_BRANCH} "$REPO_URL" .
RUN find . -type d -name '.git' | xargs rm -rf {} \;
RUN npm install
RUN apk del git

FROM node:24-alpine

WORKDIR /app

COPY --from=builder /tmp/chat ./
RUN chmod +x ./start.sh

CMD ["./start.sh"]