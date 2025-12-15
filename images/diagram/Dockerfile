ARG BASE_IMAGE=alpine:3

FROM $BASE_IMAGE AS builder


ARG TOMCAT_VERSION=10.1.49
ARG TOMCAT_SHA512SUM=a46c8e37d4767b56a16dbdd8e81b80f25ad2edd5fba68b5099b9165cfffbe32bc923a601db8bb5cba50e8b1047a7906eb8c30ca176e1c0b8dfd85fbb9c54c6c2
ARG DRAWIO_VERSION=29.2.2
ARG DRAWIO_SHA256SUM=28a043b381818b765e1c414551fbc089930166e3f217abffa60cad28ee5e9b88

RUN apk add --no-cache wget tar \
    && wget https://archive.apache.org/dist/tomcat/tomcat-10/v$TOMCAT_VERSION/bin/apache-tomcat-$TOMCAT_VERSION.tar.gz \
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

FROM $BASE_IMAGE AS main

ARG JAVA_OPTS="-Xverify:none"
ENV JAVA_OPTS=$JAVA_OPTS
ENV USER=tomcat
ARG UID=1000
ENV UID=$UID

RUN apk add --no-cache openjdk21 \
    && addgroup -g $UID $USER \
    && adduser -G $USER -u $UID --disabled-password --gecos "" $USER

COPY --from=builder --chown=tomcat:tomcat /opt/tomcat /opt/tomcat
EXPOSE 8080
USER $USER
ENTRYPOINT ["/opt/tomcat/bin/catalina.sh","run" ]
