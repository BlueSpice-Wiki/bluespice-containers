# syntax=docker/dockerfile:1
#
# nginxproxy/nginx-proxy:alpine with security updates applied on top
#
# Upstream has not merged any update since 2026-10-07. The image still ships
# nginx 1.31.6, Alpine packages from 3.24.2 and docker-gen / forego built with
# Go 1.26.5. As long as that is the case, we
#   - upgrade nginx and its modules from the signed nginx.org package repository
#     (the same packages the official nginx image uses),
#   - upgrade all Alpine packages,
#   - rebuild docker-gen and forego with a current Go toolchain and modules.
# Once upstream catches up, the upgrade steps become no-ops and the Go build
# stage can be dropped.

ARG DOCKER_GEN_VERSION=0.17.2
ARG DOCKER_GEN_SHA256=dfea32f45e8b3f0c61f93927375d538de6bb94c2089b0fb4adbbbce3289df378
ARG FOREGO_VERSION=v0.18.8
ARG FOREGO_SHA256=5d68991037fd8d3cca8e7add066dd7406938ebba00da0739ebf3f2178833280e

FROM golang:1.27-alpine AS go-builder
ARG DOCKER_GEN_VERSION
ARG DOCKER_GEN_SHA256
ARG FOREGO_VERSION
ARG FOREGO_SHA256
ENV CGO_ENABLED=0
WORKDIR /build
ADD --checksum=sha256:${DOCKER_GEN_SHA256} \
    https://github.com/nginx-proxy/docker-gen/archive/refs/tags/${DOCKER_GEN_VERSION}.tar.gz docker-gen.tar.gz
ADD --checksum=sha256:${FOREGO_SHA256} \
    https://github.com/nginx-proxy/forego/archive/refs/tags/${FOREGO_VERSION}.tar.gz forego.tar.gz
RUN mkdir docker-gen \
 && tar -xzf docker-gen.tar.gz -C docker-gen --strip-components=1 \
 && cd docker-gen \
 && go get \
      golang.org/x/crypto@latest \
      golang.org/x/text@latest \
      github.com/moby/go-archive@latest \
      github.com/klauspost/compress@latest \
 && go mod tidy \
 && go build -ldflags "-X main.buildVersion=${DOCKER_GEN_VERSION}" -o /out/docker-gen ./cmd/docker-gen
RUN mkdir forego \
 && tar -xzf forego.tar.gz -C forego --strip-components=1 \
 && cd forego \
 && go get golang.org/x/text@latest \
 && go mod tidy \
 && go build -o /out/forego .

FROM nginxproxy/nginx-proxy:alpine

# The official nginx image pins nginx and its modules to an exact version in
# /etc/apk/world. Re-adding them without a version lifts the pin, so they can be
# upgraded from the nginx.org repository like any other package.
RUN NGINX_REPO="https://nginx.org/packages/mainline/alpine/v$(cut -d. -f1,2 /etc/alpine-release)/main" \
 && apk add --no-cache --upgrade -X "$NGINX_REPO" \
      $(apk info 2>/dev/null | grep -E '^nginx(-module-[a-z-]+)?$') \
 && apk upgrade --no-cache -X "$NGINX_REPO" \
 && rm -f /etc/nginx/*.apk-new

COPY --from=go-builder /out/docker-gen /out/forego /usr/local/bin/

RUN { \
      echo 'server_tokens off;'; \
      echo 'client_max_body_size 0;'; \
    } > /etc/nginx/conf.d/bluespice.conf
