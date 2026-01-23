FROM eclipse-temurin:21-jre-alpine
ARG SHA256sum=5eb8696ebab5f409d788d04f05eee3ec48ec606f9a071b3b2a17811339bfa653
ADD --checksum=sha256:$SHA256sum https://github.com/hallowelt/webservice-html2pdf/releases/download/1.1.1/html2pdf.jar /app/html2pdf.jar
RUN chown -R 1000:1000 /app
WORKDIR /app

EXPOSE 8080
USER 1000
CMD ["java", "-jar", "html2pdf.jar"]
