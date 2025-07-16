ARG BASE_IMAGE=alpine:3.20.3

FROM $BASE_IMAGE AS builder

ARG SHA512SUM_1=fc838d5249b4059bc80ec9580bdf980e1e1226df346d20afd3751296b7d674fd46804207092d5d9e4a4b7117418d8952ae674d29412be0076bf27e7fabc27a11
ARG SHA512SUM_2=baccaf655966144ae1d62607cda5e0098c9b200da37ac7deec2aa449543d08b46946e78645d793d8ebb4ce756f9c3b84c5f8eea016d504e0e130aee95501192f

RUN apk add --no-cache wget tar \
    && wget https://archive.apache.org/dist/tomcat/tomcat-10/v10.1.43/bin/apache-tomcat-10.1.43.tar.gz \
    && echo "$SHA512SUM_1  apache-tomcat-10.1.43.tar.gz" | sha512sum -c - \
    && mkdir -p /opt/tomcat \
    && tar xzf apache-tomcat-10.1.43.tar.gz -C /opt/tomcat --strip-components 1 \
    && rm apache-tomcat-10.1.43.tar.gz

RUN wget https://github.com/jgraph/drawio/releases/download/v28.0.4/draw.war \
    && echo "$SHA512SUM_2  draw.war" | sha512sum -c - \
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
