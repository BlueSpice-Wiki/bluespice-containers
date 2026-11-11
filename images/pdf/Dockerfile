FROM eclipse-temurin:25-jre-alpine
ARG SHA256sum=1f7f0e087debe8e8630d8cb3b30ed15c91a3000bf57df4240e58f52144d5f36a
ARG JAR_URL=https://github.com/hallowelt/webservice-html2pdf/releases/download/2.2.5/html2pdf.jar
ADD $JAR_URL /app/html2pdf.jar
RUN echo "$SHA256sum  /app/html2pdf.jar" | sha256sum -c -
RUN mkdir -p /tmp/.cache/fontconfig && \
    chgrp -R 0 /app /tmp/.cache && \
    chmod -R g=u /app /tmp/.cache

# Build info: value of build arg BUILDINFO, `-` if not set
ARG BUILDINFO
RUN mkdir -p /image && echo "${BUILDINFO:--}" > /image/BUILDINFO

ENV XDG_CACHE_HOME=/tmp/.cache
WORKDIR /app

EXPOSE 8080
USER 1000
CMD ["java", "-jar", "html2pdf.jar"]
