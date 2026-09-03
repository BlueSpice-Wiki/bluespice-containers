FROM alpine:3 AS builder

ARG TOMCAT_VERSION=11.0.24
ARG TOMCAT_SHA512SUM=a2fb1bd511735bd3d135b87f628d2b1f71a43aed7c4d7511e770092e571bad6d5ad9e97a580852119770477fd86d7ed156d83d3cee2854bce260725ce48934d0
ARG DRAWIO_VERSION=31.1.2
ARG DRAWIO_SHA256SUM=05907c7d4f987673de5222350d32e64bf1a16defbf5259be3a28d156466f85c3

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

