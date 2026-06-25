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

This project includes the following open source fonts as replacements for the PDF base 14 fonts and
generic fallbacks:

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
| Noto                | noto/*                                   | SIL Open Font                              |

Font sources: [URW++ Core 35 Fonts](https://github.com/ArtifexSoftware/urw-base35-fonts), [D050000L](https://ctan.org/pkg/d050000l), [Noto](https://notofonts.github.io/)

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
{"msg":"Service is running","success":true,"version":"2.0.0"}
```

## Fonts and Multi-Script Support

Due to the focus on PDF/A compliance, every font in use must be embedded in the
PDF. We ensure this with three measures:

1. set a `font-family` on the `html` element with a list of fallback fonts
	(Nimbus Roman, Noto fonts) for many commonly used scripts

2. replace all occurences of `sans-serif`, `serif` and `monospace` in
	`font-family` CSS properties with `...-fallback`, and load the appropriate
	fonts with those names

3. append fallback fonts to all occurences of `font-family` in CSS and `<style>`
	elements

This enables us to provide support for a large range of characters. You might
still run into issues for one of the following reasons:

1. use a font that was not uploaded with the HTML file (or provided by the service)

2. set `font-family` to a generic name (`sans-serif`, `serif`, `monospace`) in
	other content, e.g., SVG files

3. use very recent Unicode code points, e.g., the newest emojis, that are not
	yet embedded in the font files

4. use scripts that are not part of our fallback stack, e.g., ancient scripts

To mitigate these problems you will need to provide your own fonts and carefully
craft your `font-family` CSS declarations, so that each glyph is found reliably.

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

## TODO
* Implement proper support for custom fonts (e.g. from inline CSS)
