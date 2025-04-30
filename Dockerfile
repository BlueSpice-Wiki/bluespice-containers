ARG BASE_IMAGE=alpine:3.20.3

FROM $BASE_IMAGE AS builder

ARG SHA512SUM_1=2bde772acf2e6f300f0f8341eb4de7da5d59af6a95f607bcdb92e4c22e0a253d437ea9a423d7d3e334af1c608f33489f32d32d346fbef5b0abef1dee666895ea
ARG SHA256SUM_2=de94bf3cf8fcc438add2b8d970d3131c2409a1470641d5e033b4e49594583866

RUN apk add --no-cache wget tar \
    && wget https://archive.apache.org/dist/tomcat/tomcat-10/v10.1.40/bin/apache-tomcat-10.1.40.tar.gz \
    && echo "$SHA512SUM_1  apache-tomcat-10.1.40.tar.gz" | sha512sum -c - \
    && mkdir -p /opt/tomcat \
    && tar xzf apache-tomcat-10.1.40.tar.gz -C /opt/tomcat --strip-components 1 \
    && rm apache-tomcat-10.1.40.tar.gz

RUN wget https://github.com/jgraph/drawio/releases/download/v26.2.15/draw.war \
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
