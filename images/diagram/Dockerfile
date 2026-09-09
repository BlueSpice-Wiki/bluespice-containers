FROM alpine:3 AS builder

ARG TOMCAT_VERSION=11.0.25
ARG TOMCAT_SHA512SUM=81339c046dff1b363a80a3bccf80cb391660a6828dd8ae042180ceb11c8b1614317143e60b311b9e791dab585bb046b777234667acce7dca2203a74b37bf20f2
ARG DRAWIO_VERSION=31.4.4
ARG DRAWIO_SHA256SUM=c3fcd289a45928baab4887b864daad3a8fb7b4fe9da175db065ddf661d4701ca

RUN apk add --no-cache wget tar \
    && (wget https://downloads.apache.org/tomcat/tomcat-11/v$TOMCAT_VERSION/bin/apache-tomcat-$TOMCAT_VERSION.tar.gz \
        || wget https://archive.apache.org/dist/tomcat/tomcat-11/v$TOMCAT_VERSION/bin/apache-tomcat-$TOMCAT_VERSION.tar.gz) \
    && echo "$TOMCAT_SHA512SUM  apache-tomcat-$TOMCAT_VERSION.tar.gz" | sha512sum -c - \
    && mkdir -p /opt/tomcat \
    && tar xzf apache-tomcat-$TOMCAT_VERSION.tar.gz -C /opt/tomcat --strip-components 1 \
    && rm apache-tomcat-$TOMCAT_VERSION.tar.gz

RUN wget https://github.com/jgraph/drawio/releases/download/v$DRAWIO_VERSION/draw.war \
    && echo "$DRAWIO_SHA256SUM  draw.war" | sha256sum -c - \
        && rm -fr /opt/tomcat/webapps/* \
    && unzip draw.war -d /opt/tomcat/webapps/_diagram \
    && ln -sf /opt/tomcat/webapps/_diagram /opt/tomcat/webapps/ROOT \
    && rm -rf draw.war

FROM alpine:3 AS main

ARG JAVA_OPTS="-Xverify:none"
ENV JAVA_OPTS=$JAVA_OPTS
ENV USER=tomcat
ARG UID=1000
ENV UID=$UID

RUN apk add --no-cache openjdk21 \
    && adduser -G root -u $UID -D -g "" $USER

COPY --from=builder --chown=$UID:0 /opt/tomcat /opt/tomcat
RUN chmod -R g=u /opt/tomcat
EXPOSE 8080
USER $UID
ENTRYPOINT ["/opt/tomcat/bin/catalina.sh","run" ]

