FROM debian:trixie-slim AS base
ENV TZ=CET
ENV LANG=C.UTF-8
ENV LC_ALL=C.UTF-8
ENV DEBIAN_FRONTEND=noninteractive
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
RUN apt update \
        && apt install -y --no-install-recommends gnupg2  curl

RUN	apt install -y krb5-config \
	krb5-locales \
	krb5-user \
	apache2 \
	apache2-bin \
	apache2-data \
	apache2-utils \
	libapache2-mod-auth-gssapi \
	&& apt clean \
	&& rm -rf /var/lib/apt/lists/*

RUN a2enmod proxy proxy_http proxy_balancer lbmethod_byrequests headers
COPY root-fs/etc/apache2/sites-available/kerberos-proxy.conf /etc/apache2/sites-available/000-default.conf
COPY root-fs/app /app
EXPOSE 80
CMD ["apachectl", "-D", "FOREGROUND"]
