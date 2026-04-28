FROM composer:2 AS workaround-erm-45965
# Build SimpleSAMLphp from source, bypassing the original way of loading
ENV SIMPLESAMLPHP_VERSION=2.3.10
WORKDIR /build
RUN git clone https://github.com/simplesamlphp/simplesamlphp.git -b v${SIMPLESAMLPHP_VERSION} /build/simplesamlphp && \
	cd /build/simplesamlphp && \
	composer require psr/http-message:^1.1 psr/container:^1.1 --update-no-dev --prefer-dist --optimize-autoloader && \
	rm -rf /build/simplesamlphp/.git

FROM alpine:3 AS builder

# We use SimpleSAMLphp SLIM version to reduce the image size
ENV SIMPLESAMLPHP_VERSION=2.4.4
ENV SIMPLESAMLPHP_URL=https://github.com/simplesamlphp/simplesamlphp/releases/download/v${SIMPLESAMLPHP_VERSION}/simplesamlphp-${SIMPLESAMLPHP_VERSION}-slim.tar.gz
ENV SIMPLESAMLPHP_SHA256=1c351342293d218447b27df9a03d2da7561d3831303524c173e8974b3168a57e

ENV MATHOIDREMOTE_URL=https://raw.githubusercontent.com/hallowelt/docker-bluespice-formula/5.1.x/_client/mathoid-remote
ENV MATHOIDREMOTE_SHA256=9a562346e8fcc662f2d4b1c2a674c23862782365807d30de317a1fe77affc36a

ENV MEDIAWIKIADM_URL=https://github.com/hallowelt/misc-mediawiki-adm/releases/latest/download/mediawiki-adm
ENV MEDIAWIKIADM_SHA256=c038de94ce49c66584556bc03c58bf0f9388d109b9428c800d0b74c90b528f15

ENV PARALLELRUNJOBSSERVICE_URL=https://github.com/hallowelt/misc-parallel-runjobs-service/releases/latest/download/parallel-runjobs-service
ENV PARALLELRUNJOBSSERVICE_SHA256=ec8ea7e8a79242baba862448bcba952376267c0db19785d6266ab9a01a29e241

ARG BLUESPICE_VERSION=5.2.2
ARG BLUESPICE_URL=https://github.com/BlueSpice-Wiki/bluespice-free-release.git

WORKDIR /build
ADD --checksum=sha256:${SIMPLESAMLPHP_SHA256} ${SIMPLESAMLPHP_URL} /build/simplesamlphp.tar.gz
ADD --checksum=sha256:${MATHOIDREMOTE_SHA256} ${MATHOIDREMOTE_URL} /build/mathoid-remote
ADD --checksum=sha256:${MEDIAWIKIADM_SHA256} ${MEDIAWIKIADM_URL} /build/mediawiki-adm
ADD --checksum=sha256:${PARALLELRUNJOBSSERVICE_SHA256} ${PARALLELRUNJOBSSERVICE_URL} /build/parallel-runjobs-service
# HINT: Based on the type of URL, this can already be a tarball ( "Release-URL" ) or a git repository checkout ( "pre-release"/"custom-edition" )
ADD ${BLUESPICE_URL}#${BLUESPICE_VERSION} /build/bluespice

RUN mkdir -p /build/simplesamlphp && \
	tar -xzf /build/simplesamlphp.tar.gz -C /build/simplesamlphp --strip-components=1
RUN rm -rf /build/.git

FROM alpine:3 AS base
ENV LANG=C.UTF-8
ENV LC_ALL=C.UTF-8
ENV VERSION=84
RUN apk add \
	bash \
	ca-certificates \
	clamav-clamdscan \
	ghostscript \
	imagemagick \
	libc6-compat \
	nginx \
	openssl \
	php \
	php$VERSION-apcu \
	php$VERSION-cli \
	php$VERSION-ctype \
	php$VERSION-curl \
	php$VERSION-fileinfo \
	php$VERSION-fpm \
	php$VERSION-gd \
	php$VERSION-gmp \
	php$VERSION-iconv \
	php$VERSION-intl \
	php$VERSION-json \
	php$VERSION-ldap \
	php$VERSION-mbstring \
	php$VERSION-mysqli \
	php$VERSION-opcache \
	php$VERSION-pdo \
	php$VERSION-pdo_mysql \
	php$VERSION-pdo_pgsql \
	php$VERSION-pdo_sqlite \
	php$VERSION-pgsql \
	php$VERSION-phar \
	php$VERSION-posix \
	php$VERSION-session \
	php$VERSION-simplexml \
	php$VERSION-sqlite3 \
	php$VERSION-tokenizer \
	php$VERSION-xml \
	php$VERSION-xmlreader \
	php$VERSION-xmlwriter \
	php$VERSION-zip \
	poppler-utils \
	procps \
	python3 \
	rsvg-convert \
	supercronic \
	tzdata \
	vim \
	xpdf \
	&& echo "@testing https://dl-cdn.alpinelinux.org/alpine/edge/testing" >> /etc/apk/repositories \
	&& apk add php$VERSION-pecl-excimer@testing
RUN echo "@edge https://dl-cdn.alpinelinux.org/alpine/edge/main" >> /etc/apk/repositories \
	&& apk add openjpeg@edge
FROM base AS bluespice-prepare
ENV PATH="/app/bin:${PATH}"
ARG UID
ENV UID=1002
ARG USER
ENV USER=bluespice
ENV PATH="/app/bin:${PATH}"
ARG GID
ENV GID=$UID
ARG GROUPNAME
ENV GROUPNAME=$USER

RUN addgroup -g $GID $GROUPNAME \
	&& adduser -u $UID -G $GROUPNAME --shell /bin/bash --disabled-password --gecos "" $USER \
	&& addgroup $USER nginx \
	&& mkdir -p /app/bluespice \
	&& chown $USER:$GROUPNAME /app/bluespice/ \
	&& chmod -R 777 /var/log
COPY --chown=$USER:$GROUPNAME --from=builder /build/bluespice /app/bluespice/w
COPY --chown=$USER:$GROUPNAME --from=workaround-erm-45965 /build/simplesamlphp /app/simplesamlphp
COPY --chown=$USER:$GROUPNAME --chmod=755 ./root-fs/app/bin /app/bin
COPY --chown=$USER:$GROUPNAME --chmod=666 ./root-fs/app/bin/config /app/bin/config
COPY --chown=$USER:$GROUPNAME ./root-fs/app/conf /app/conf
COPY --chown=$USER:$GROUPNAME ./root-fs/app/simplesamlphp/ /app/simplesamlphp
COPY --chown=$USER:$GROUPNAME ./root-fs/app/cron /app/cron
COPY --chown=$USER:$GROUPNAME --from=builder --chmod=755 /build/mathoid-remote /app/bin
COPY --chown=$USER:$GROUPNAME --from=builder --chmod=755 /build/mediawiki-adm /app/bin
COPY --chown=$USER:$GROUPNAME --from=builder --chmod=755 /build/parallel-runjobs-service /app/bin
COPY ./root-fs/etc/php/8.x/fpm/php-fpm.conf /etc/php$VERSION
COPY ./root-fs/etc/php/8.x/fpm/pool.d/www.conf /etc/php$VERSION/php-fpm.d/
COPY ./root-fs/etc/nginx/nginx.conf /etc/nginx/nginx.conf

ENV BASH_ENV=/app/.env

RUN ln -sf /usr/sbin/php-fpm$VERSION /usr/bin/php-fpm \
	&& mkdir /var/run/php \
	&& ln -sf /usr/bin/php$VERSION /usr/bin/php \
	&& ln -sf /usr/bin/php$VERSION /bin/php \
	&& mkdir -p /etc/nginx/sites-enabled \
	&& ln -s /app/conf/90-bluespice-overrides.ini /etc/php$VERSION/conf.d/90-bluespice-overrides.ini \
	&& ln -s /app/conf/nginx_bluespice /etc/nginx/sites-enabled/default \
	&& chown -R $USER:$GROUPNAME /var/run/php \
	&& mkdir -p /etc/clamav/ \
	&& ln -s /app/bin/config/clamd.conf /etc/clamav/clamd.conf \
	&& touch /app/.env \
	&& chown $USER:$GROUPNAME /app/.env \
	&& chmod 660 /app/.env \
	&& sed -i '1isource /app/.env' /etc/bash/bashrc

ARG EDITION # Intentionally left uninitialized
RUN if [ -n "$EDITION" ]; then \
		echo "EDITION=$EDITION" > /app/.env; \
	fi
RUN if [[ $(cat /app/bluespice/w/BLUESPICE-EDITION) == "cloud" ]] ; then \
	apk add curl; \
	fi
FROM bluespice-prepare AS bluespice-final
WORKDIR /app
USER bluespice
EXPOSE 9090
HEALTHCHECK --interval=30s --timeout=5s CMD probe-liveness
ENTRYPOINT ["/app/bin/entrypoint"]
