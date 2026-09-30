FROM eclipse-temurin:25-jre-alpine
ARG SHA256sum=a70a5da3f7ec8137ba0bdd1dbdf1d633c7b128cfd94c4d5fd74da90740a08e17
ARG JAR_URL=https://github.com/hallowelt/webservice-html2pdf/releases/download/2.2.2/html2pdf.jar
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
