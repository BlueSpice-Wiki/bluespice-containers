ARG BASE_IMAGE=alpine:3.20.3

FROM $BASE_IMAGE AS builder

ARG SHA512SUM_1=20da89fa77fb8d4dbfccf6c68383751348169542aad9d3cbcaf82011337355b9847b883cc71678fa6cc71ef3f55e8d5d7a09a53238b86011816fa989f9c4ff5e
ARG SHA512SUM_2=200a31f98fdfec1068e0942dfc348d28ebddae39ad840a069dff96611fd1d12aa55386b3feb21a69fde9bc1616faf9c109ec51e91039bc06426eff84cb7616a6

RUN apk add --no-cache wget tar \
    && wget https://archive.apache.org/dist/tomcat/tomcat-10/v10.1.46/bin/apache-tomcat-10.1.46.tar.gz \
    && echo "$SHA512SUM_1  apache-tomcat-10.1.46.tar.gz" | sha512sum -c - \
    && mkdir -p /opt/tomcat \
    && tar xzf apache-tomcat-10.1.46.tar.gz -C /opt/tomcat --strip-components 1 \
    && rm apache-tomcat-10.1.46.tar.gz

RUN wget https://github.com/jgraph/drawio/releases/download/v28.2.0/draw.war \
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
