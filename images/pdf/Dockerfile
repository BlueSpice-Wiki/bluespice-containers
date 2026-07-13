FROM eclipse-temurin:25-jre-alpine
ARG SHA256sum=2e0d79b9a85da4beff0402cc6e8db3a1622bf41f8c17949fd769714199b18f3a
ARG JAR_URL=https://github.com/hallowelt/webservice-html2pdf/releases/download/2.0.0/html2pdf.jar
ADD $JAR_URL /app/html2pdf.jar
RUN echo "$SHA256sum  /app/html2pdf.jar" | sha256sum -c -
RUN chown -R 1000:1000 /app
RUN mkdir -p /tmp/.cache/fontconfig && chown -R 1000:1000 /tmp/.cache
ENV XDG_CACHE_HOME=/tmp/.cache
WORKDIR /app

EXPOSE 8080
USER 1000:1000
CMD ["java", "-jar", "html2pdf.jar"]
