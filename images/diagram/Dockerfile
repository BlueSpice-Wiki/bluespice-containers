ARG BASE_IMAGE=alpine:3

FROM $BASE_IMAGE AS builder


ARG TOMCAT_VERSION=10.1.50
ARG TOMCAT_SHA512SUM=c7702b0304257d80dc5bd615005fe037bd0c518e3fe77d22a58e5313fe53e6af6f4a2cf00790e3e9a669d1ae5470fb11177c9ef42f8c846d2b20dfac93e2d551
ARG DRAWIO_VERSION=29.2.9
ARG DRAWIO_SHA256SUM=2b2583f359171652aa157450e191c9cd2eae86f17960829cfe517b6d833df645

RUN apk add --no-cache wget tar \
    && (wget https://downloads.apache.org/tomcat/tomcat-10/v$TOMCAT_VERSION/bin/apache-tomcat-$TOMCAT_VERSION.tar.gz \
        || wget https://archive.apache.org/dist/tomcat/tomcat-10/v$TOMCAT_VERSION/bin/apache-tomcat-$TOMCAT_VERSION.tar.gz) \
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
