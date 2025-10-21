# Html2PDF Webservice

This is a very simple webservice, that uses the amazing [OpenHTML2PDF](https://github.com/openhtmltopdf/openhtmltopdf) to convert HTML to PDF.

Currently there are two client implementations available:
- [Extension:PDFCreator](https://www.mediawiki.org/wiki/Extension:PDFCreator).
- [Extension:BlueSpiceUEModulePDF](https://www.mediawiki.org/wiki/Extension:BlueSpiceUEModulePDF)

## JAR file build
To build the JAR file, run:
```bash
mvn clean package
```

## Docker image
Build the docker image with:
```bash
docker build -t webservice-htmlpdf .
```

Run the container with:
```
docker run -p 8080:8080 webservice-htmlpdf
```
## Included Fonts and Licenses

This project includes the following open source fonts as replacements for the PDF base 14 fonts:

| Font Family         | Font Files (TTF)                        | License                                    |
|---------------------|------------------------------------------|---------------------------------------------|
| Nimbus Roman        | NimbusRoman-Regular, -Bold, -Italic,     | GPL or AFPL (URW++)                        |
|                     | -BoldItalic                              |                                             |
| Nimbus Sans         | NimbusSans-Regular, -Bold, -Italic,      | GPL or AFPL (URW++)                        |
|                     | -BoldItalic                              |                                             |
| Nimbus Mono PS      | NimbusMonoPS-Regular, -Bold, -Italic,    | GPL or AFPL (URW++)                        |
|                     | -BoldItalic                              |                                             |
| Standard Symbols PS | StandardSymbolsPS                        | GPL or AFPL (URW++)                        |
| D050000L            | D050000L                                 | LaTeX Project Public License (LPPL)         |

Font sources: [URW++ Core 35 Fonts](https://github.com/ArtifexSoftware/urw-base35-fonts), [D050000L](https://ctan.org/pkg/d050000l)

Please refer to the respective repositories for full license texts.

## Manual testing
To test the service manually, run:
```bash
cd manual-testing
composer update
php test.php data/doc1/
```

## Liveness probing and version check
To check if the service is responsive one can execute a HTTP request against the `servlet.context-path` (default `/Html2PDF/v1/`)

Example:
```bash
curl http://localhost:8080/Html2PDF/v1/
```
will return somthing like:
```
{"msg":"Service is running","success":true,"version":"1.1.0"}
```

## "bshtml2pdf" compatibility
The old "bshtml2pdf" service ran servlets on a Tomcat server and hat `/BShtml2PDF` as the base URL. This service runs standalone and therefore lacks the `/BShtml2PDF` base URL. The client implementation must be adjusted accordingly.

If that can not be done easily, one can start the service with the `server.servlet.context-path` parameter:

```bash
java -jar \
	-Dserver.servlet.context-path=/BShtml2PDF \
	htmlpdf-1.0.0-SNAPSHOT.jar
```

or using the Docker image:

```bash
docker run -p 8080:8080 -e APP_PATH=/BShtml2PDF webservice-htmlpdf
```

# TODO:
* Implement proper support for custom fonts (e.g. from inline CSS)
