ARG BASE_IMAGE=alpine:3.20.3

FROM $BASE_IMAGE AS builder

ARG SHA512SUM_1=bba43488c1fbcaeaaee1c7c6f3bb2464f10bb1c23f35444d7df1e4d55a6b1838d7d2ca20289f294322f181a6b6e58691d1f75dc50e0f57c2d93eb2fccd35e795
ARG SHA256SUM_2=35728aeb587f539685819825cc9f1a2fe77455ad1270e1f5c87acdfc6f56abc8

RUN apk add --no-cache wget tar \
    && wget https://archive.apache.org/dist/tomcat/tomcat-10/v10.1.41/bin/apache-tomcat-10.1.41.tar.gz \
    && echo "$SHA512SUM_1  apache-tomcat-10.1.41.tar.gz" | sha512sum -c - \
    && mkdir -p /opt/tomcat \
    && tar xzf apache-tomcat-10.1.41.tar.gz -C /opt/tomcat --strip-components 1 \
    && rm apache-tomcat-10.1.41.tar.gz

RUN wget https://github.com/jgraph/drawio/releases/download/v27.0.5/draw.war \
    && echo "$SHA256SUM_2  draw.war" | sha256sum -c - \
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
