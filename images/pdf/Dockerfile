FROM eclipse-temurin:25-jre-alpine
ARG SHA256sum=7bf34f46cd73b18d8df4ec2914eb70a2e128fe11e9dd362a61bda350d499a69e
ARG JAR_URL=https://github.com/hallowelt/webservice-html2pdf/releases/download/2.2.0/html2pdf.jar
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
