FROM eclipse-temurin:25-jre-alpine
ARG SHA256sum=b6e5ca2ea032424976c7efec2d47cf3c35df4e1c64a4f53f1e01668ea313b0a4
ARG JAR_URL=https://github.com/hallowelt/webservice-html2pdf/releases/download/2.2.3/html2pdf.jar
ADD $JAR_URL /app/html2pdf.jar
RUN echo "$SHA256sum  /app/html2pdf.jar" | sha256sum -c -
RUN mkdir -p /tmp/.cache/fontconfig && \
    chgrp -R 0 /app /tmp/.cache && \
    chmod -R g=u /app /tmp/.cache
ENV XDG_CACHE_HOME=/tmp/.cache
WORKDIR /app

EXPOSE 8080
USER 1000
CMD ["java", "-jar", "html2pdf.jar"]
