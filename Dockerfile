FROM eclipse-temurin:21-jre-alpine
ARG SHA256sum=8a70229ba2f6bf493603ab669eeccb5bd92a3cbdde24f7a78cd1467ed49c56c7
ADD --checksum=sha256:$SHA256sum https://github.com/hallowelt/webservice-html2pdf/releases/download/1.1.2/html2pdf.jar /app/html2pdf.jar
RUN chown -R 1000:1000 /app
WORKDIR /app

EXPOSE 8080
USER 1000
CMD ["java", "-jar", "html2pdf.jar"]
