ARG BASE_IMAGE=alpine:3.20.3

FROM $BASE_IMAGE AS builder

ARG SHA512SUM_1=aecbc4ae16f6783e3f80696fe936c8201fd74a708be18a2512864c0141eeec91180b8c8274f60a0e28390d932344a15c5ef3b3e6fbb819b3d2db244d4f562998
ARG SHA512SUM_2=016b4ec30120198b75083bf842fc8de9866d78a6cfa6d006161ebc7a6c7a4218dbf5566e382c9a104ec6bc96cfdb7e310e3400b3c0dd58cc703fa6dfd427a47e

RUN apk add --no-cache wget tar \
    && wget https://archive.apache.org/dist/tomcat/tomcat-10/v10.1.48/bin/apache-tomcat-10.1.48.tar.gz \
    && echo "$SHA512SUM_1  apache-tomcat-10.1.48.tar.gz" | sha512sum -c - \
    && mkdir -p /opt/tomcat \
    && tar xzf apache-tomcat-10.1.48.tar.gz -C /opt/tomcat --strip-components 1 \
    && rm apache-tomcat-10.1.48.tar.gz

RUN wget https://github.com/jgraph/drawio/releases/download/v28.2.5/draw.war \
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
