FROM node:24-alpine AS builder

WORKDIR /tmp/chat

ARG REPO_BRANCH="master"
# To be eventually replaced by clone of a build release
RUN --mount=type=secret,id=gitlab-token,env=GITLAB_TOKEN \
	apk update \
	&& apk add --no-cache git \
	&& git clone --depth=1 -b ${REPO_BRANCH} "https://oauth:${GITLAB_TOKEN}@gitlab.hallowelt.com/BlueSpice/webservice-wire.git" . \
	&& find . -type d -name '.git' | xargs rm -rf {} \; \
	&& npm install \
	&& apk del git

FROM node:24-alpine

WORKDIR /app

COPY --from=builder /tmp/chat ./
RUN chmod +x ./start.sh

CMD ["./start.sh"]