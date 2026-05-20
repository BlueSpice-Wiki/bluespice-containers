FROM eclipse-temurin:21-jre-alpine
ARG SHA256sum=e8f5dda1c1aedd722597af9d1c08856a73448a15d72d06db63a4850d677519c6
ADD --checksum=sha256:$SHA256sum https://github.com/hallowelt/webservice-html2pdf/releases/download/1.1.3/html2pdf.jar /app/html2pdf.jar
RUN chown -R 1000:1000 /app
WORKDIR /app

EXPOSE 8080
USER 1000
CMD ["java", "-jar", "html2pdf.jar"]
