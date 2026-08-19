FROM eclipse-temurin:25-jre-alpine
ARG SHA256sum=767efdeb28d5a6dedae6d2ed4b13f43ac9f55119583bddf389a8a427e5972616
ARG JAR_URL=https://github.com/hallowelt/webservice-html2pdf/releases/download/2.1.1/html2pdf.jar
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
