FROM node:18-alpine AS builder

RUN apk update && apk add --no-cache git
RUN git clone --depth 1 https://gitlab.wikimedia.org/repos/mediawiki/services/mathoid.git /tmp/mathoid
RUN cd /tmp/mathoid && npm install --omit=dev
RUN cp /tmp/mathoid/config.dev.yaml /tmp/mathoid/config.yaml
RUN find /tmp/mathoid -type d -name '.git' | xargs rm -rf {} \;
RUN find /tmp/mathoid -type d -name 'test' | xargs rm -rf {} \;
RUN apk del git

FROM node:18-alpine

RUN apk update && apk add --no-cache librsvg
RUN mkdir -p /opt/mathoid

COPY --from=builder /tmp/mathoid /opt/mathoid
COPY ./opt/init.sh /opt/init.sh

EXPOSE 10044

ENTRYPOINT [ "/opt/init.sh"]