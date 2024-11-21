FROM debian:bookworm-slim AS base
ENV TZ=CET
ENV LANG=C.UTF-8
ENV LC_ALL=C.UTF-8
ENV DEBIAN_FRONTEND=noninteractive
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone \
        && apt-get update  \
        && apt-get -y install --no-install-recommends gnupg2 curl  \
        && touch /etc/apt/sources.list.d/trixie.list && printf "deb http://deb.debian.org/debian trixie main" > /etc/apt/sources.list.d/trixie.list \
        && apt-get update \
        && apt-get --only-upgrade install zlib1g
FROM  base AS finish
RUN apt update \
	&& apt-get install -y krb5-config \
	krb5-locales \
	krb5-user \
	apache2-bin=2.4.62-1~deb12u2 \
	apache2-data=2.4.62-1~deb12u2 \
	apache2-utils=2.4.62-1~deb12u2 \
	apache2=2.4.62-1~deb12u2 \
	libapache2-mod-proxy-uwsgi=2.4.62-1~deb12u2 \
	libapache2-mod-auth-gssapi
RUN a2enmod proxy proxy_http proxy_balancer lbmethod_byrequests headers
COPY root-fs/etc/apache2/sites-available/kerberos-proxy.conf /etc/apache2/sites-available/000-default.conf
COPY root-fs/app /app
EXPOSE 80
CMD ["apachectl", "-D", "FOREGROUND"]
