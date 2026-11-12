package com.hallowelt.mediawiki.services.html2pdf;

import static org.junit.jupiter.api.Assertions.assertNotNull;
import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertNull;
import static org.junit.jupiter.api.Assertions.assertTrue;
import static org.junit.jupiter.api.Assertions.assertNotEquals;

import java.util.UUID;
import java.io.File;
import java.nio.file.Files;
import java.nio.file.Path;

import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.condition.EnabledIfSystemProperty;
import org.junit.jupiter.params.ParameterizedTest;
import org.junit.jupiter.params.provider.CsvSource;
import org.junit.jupiter.params.provider.ValueSource;
import org.jsoup.Jsoup;
import org.jsoup.parser.Parser;
import org.apache.pdfbox.Loader;
import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.pdmodel.documentinterchange.logicalstructure.PDStructureElement;
import org.apache.pdfbox.pdmodel.documentinterchange.logicalstructure.PDStructureNode;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.boot.test.context.SpringBootTest;
import org.springframework.context.ApplicationContext;
import org.springframework.mock.web.MockHttpServletResponse;
import org.springframework.test.util.ReflectionTestUtils;
import org.springframework.util.FileSystemUtils;

@SpringBootTest
class Html2PdfApplicationTests {

	@Autowired
	private ApplicationContext applicationContext;

	@Autowired
	private MainController controller;

	@Test
	void contextLoads() {
		assertNotNull(applicationContext, "The application context should have loaded.");
	}

	@Test
	void fallbackFontMetricsAreNormalized() {
		var mapping = new FallbackFontMapping();
		String fallbackFamily = mapping.getFontFamilyNames().split(",")[0].replace("'", "").trim();
		assertNotNull(mapping.getMetricsOverride("font-metrics2:" + fallbackFamily + ":400:NORMAL"));
		assertNull(mapping.getMetricsOverride("font-metrics2:CustomFont:400:NORMAL"));
		assertNull(mapping.getMetricsOverride("font-metrics:CustomFont:400:NORMAL"));
	}

	@Test
	void exposesGeneratedBuildIdentity() {
		var info = controller.index();
		assertNotNull(info.get("version"));
		assertNotNull(info.get("buildId"));
		assertNotNull(info.get("buildTime"));
		assertNotEquals("unversioned", info.get("buildId"), "Run Maven generate-resources to create build metadata.");
		assertNotEquals("unknown", info.get("buildTime"));
	}

	@Test
	void generatedPdfContainsBuildIdentity() throws Exception {
		rendersTextboxWithAccessibility("textbox-block");
	}

	@Test
	void missingDocumentReturnsServerErrorInsteadOfEmptySuccess() {
		MockHttpServletResponse response = new MockHttpServletResponse();
		controller.renderPDF("test-" + UUID.randomUUID(), "missing-document", false, response);
		assertEquals(500, response.getStatus());
		assertEquals("PDF rendering failed.", response.getErrorMessage());
		assertEquals(0, response.getContentAsByteArray().length);
		assertEquals(controller.index().get("buildId"), response.getHeader("X-Html2PDF-Build-Id"));
	}

	@ParameterizedTest
	@CsvSource({ "inline,base64", "image,base64", "inline,percent", "image,percent", "inline,local", "image,local",
			"inline,blocked", "image,blocked", "inline,blocked-file", "image,blocked-file",
			"inline,blocked-http", "image,blocked-http", "inline,blocked-https", "image,blocked-https",
			"inline,blocked-svg12", "image,blocked-svg12", "inline,nested-blocked", "image,nested-blocked",
			"inline,gif", "image,gif", "inline,gif87", "image,gif87", "inline,nested-gif", "image,nested-gif" })
	void rendersNestedSvgDataImage(String embedding, String encoding) throws Exception {
		String blockedHref = switch (encoding) {
			case "blocked-file" -> "file:///etc/passwd";
			case "blocked-http" -> "http://example.invalid/image.svg";
			case "blocked-https" -> "https://example.invalid/image.svg";
			default -> "Testfile/proxy_011.htm";
		};
		String blockedImage = "<image width='10' height='10' xlink:href='" + blockedHref + "'/>";
		String innerSvg = "<svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' width='40' height='40'>"
				+ "<defs><rect id='box' width='40' height='40' fill='red'/></defs><use xlink:href='#box'/>"
				+ ("nested-blocked".equals(encoding) ? blockedImage : "") + "</svg>";
		String dataUri = "percent".equals(encoding)
				? "data:image/svg+xml," + java.net.URLEncoder.encode(innerSvg, java.nio.charset.StandardCharsets.UTF_8).replace("+", "%20")
				: "data:image/svg+xml;base64," + java.util.Base64.getEncoder()
						.encodeToString(innerSvg.getBytes(java.nio.charset.StandardCharsets.UTF_8));
		if (encoding.endsWith("gif") || "gif87".equals(encoding)) {
			var gif = new java.awt.image.BufferedImage(40, 40, java.awt.image.BufferedImage.TYPE_INT_RGB);
			var graphics = gif.createGraphics();
			try {
				graphics.setColor(java.awt.Color.RED);
				graphics.fillRect(0, 0, 40, 40);
			} finally {
				graphics.dispose();
			}
			var bytes = new java.io.ByteArrayOutputStream();
			assertTrue(javax.imageio.ImageIO.write(gif, "gif", bytes));
			byte[] gifBytes = bytes.toByteArray();
			if ("gif87".equals(encoding)) {
				gifBytes[4] = '7';
			}
			dataUri = "data:image/gif;base64," + java.util.Base64.getEncoder().encodeToString(gifBytes);
			if ("nested-gif".equals(encoding)) {
				String nestedSvg = innerSvg.replace("<use xlink:href='#box'/>",
						"<image width='40' height='40' xlink:href='" + dataUri + "'/>");
				dataUri = "data:image/svg+xml;base64," + java.util.Base64.getEncoder()
						.encodeToString(nestedSvg.getBytes(java.nio.charset.StandardCharsets.UTF_8));
			}
		}
		String outerSvg = "local".equals(encoding) ? innerSvg
				: "<svg" + ("blocked-svg12".equals(encoding) ? " version='1.2'" : "")
				+ " xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' width='40' height='40'>"
				+ "<image width='40' height='40' xlink:href='" + dataUri + "'/></svg>";
		if (encoding.startsWith("blocked")) {
			outerSvg = outerSvg.replace("</svg>", blockedImage + "</svg>");
		}
		File tempDirectory = (File) ReflectionTestUtils.getField(controller, "tempPathFile");
		assertNotNull(tempDirectory);
		String wikiId = "nested-svg-" + UUID.randomUUID();
		String token = "nested-svg";
		Path wikiDirectory = tempDirectory.toPath().resolve(wikiId);
		Path documentDirectory = wikiDirectory.resolve(token);
		try {
			Files.createDirectories(documentDirectory);
			Files.writeString(documentDirectory.resolve("nested.svg"), outerSvg);
			String content = "inline".equals(embedding) ? outerSvg : "<img src='nested.svg' alt='Red square'/>";
			Files.writeString(documentDirectory.resolve(token + ".html"),
					"<!DOCTYPE html><html lang='en'><head><title>Nested SVG</title></head><body>" + content + "</body></html>");
			MockHttpServletResponse response = new MockHttpServletResponse();
			controller.renderPDF(wikiId, token, true, response);
			assertEquals(200, response.getStatus(), response.getErrorMessage());
			assertEquals(outerSvg, Files.readString(documentDirectory.resolve("nested.svg")));
			try (PDDocument pdf = Loader.loadPDF(response.getContentAsByteArray())) {
				java.awt.image.BufferedImage image = new org.apache.pdfbox.rendering.PDFRenderer(pdf).renderImage(0);
				int redPixels = 0;
				for (int row = 0; row < image.getHeight(); row++) {
					for (int column = 0; column < image.getWidth(); column++) {
						java.awt.Color color = new java.awt.Color(image.getRGB(column, row));
						if (color.getRed() > 200 && color.getGreen() < 50 && color.getBlue() < 50) {
							redPixels++;
						}
					}
				}
				assertTrue(redPixels > 400, embedding + " SVG should render its nested red square, found " + redPixels + " red pixels");
			}
		} finally {
			FileSystemUtils.deleteRecursively(wikiDirectory);
		}
	}

	@Test
	void gifDecoderPreservesTransparency() throws Exception {
		var source = new java.awt.image.BufferedImage(2, 1, java.awt.image.BufferedImage.TYPE_INT_ARGB);
		source.setRGB(1, 0, java.awt.Color.RED.getRGB());
		var bytes = new java.io.ByteArrayOutputStream();
		assertTrue(javax.imageio.ImageIO.write(source, "gif", bytes));
		var decoder = new GifImageRegistryEntry();
		try (var stream = new java.io.ByteArrayInputStream(bytes.toByteArray())) {
			assertTrue(decoder.isCompatibleStream(stream));
			var image = decoder.handleStream(stream, null, false).createDefaultRendering();
			var raster = image.getData();
			int transparentPixel = image.getColorModel().getRGB(raster.getDataElements(0, 0, null));
			int redPixel = image.getColorModel().getRGB(raster.getDataElements(1, 0, null));
			assertEquals(0, transparentPixel >>> 24);
			assertEquals(java.awt.Color.RED.getRGB(), redPixel);
		}
	}

	@Test
	void blockedSvgImageFallbackDoesNotHideOtherErrors() {
		var document = org.apache.batik.anim.dom.SVGDOMImplementation.getDOMImplementation()
				.createDocument("http://www.w3.org/2000/svg", "svg", null);
		var image = document.createElementNS("http://www.w3.org/2000/svg", "image");
		image.setAttributeNS("http://www.w3.org/1999/xlink", "xlink:href", "data:image/svg+xml,%3Csvg/%3E");
		document.getDocumentElement().appendChild(image);
		var expected = new org.apache.batik.bridge.BridgeException(null, image, "Unrelated rendering failure");
		var imageBridge = new ResilientSvgImageBridge() {
			@Override
			protected org.apache.batik.gvt.GraphicsNode createImageGraphicsNode(
					org.apache.batik.bridge.BridgeContext context, org.w3c.dom.Element element,
					org.apache.batik.util.ParsedURL url) {
				throw expected;
			}
		};
		var userAgent = new com.openhtmltopdf.svgsupport.OpenHtmlUserAgent(null, false, false, java.util.Set.of("data"));
		var context = new org.apache.batik.bridge.BridgeContext(userAgent);
		try {
			org.junit.jupiter.api.Assertions.assertSame(expected,
					org.junit.jupiter.api.Assertions.assertThrows(org.apache.batik.bridge.BridgeException.class,
							() -> imageBridge.buildImageGraphicsNode(context, image)));
		} finally {
			context.dispose();
		}
	}

	@Test
	void svgDataSupportKeepsExternalResourcesAndScriptsBlocked() {
		var userAgent = new com.openhtmltopdf.svgsupport.OpenHtmlUserAgent(
				new com.openhtmltopdf.svgsupport.PDFTranscoder.OpenHtmlFontResolver(), false, false, java.util.Collections.singleton("data"));
		var documentUrl = new org.apache.batik.util.ParsedURL("data:image/svg+xml,%3Csvg/%3E");
		org.junit.jupiter.api.Assertions.assertDoesNotThrow(() -> userAgent.checkLoadExternalResource(documentUrl, null));
		org.junit.jupiter.api.Assertions.assertThrows(SecurityException.class,
				() -> userAgent.checkLoadExternalResource(
						new org.apache.batik.util.ParsedURL(documentUrl, "Testfile/proxy_011.htm"), documentUrl));
		for (String uri : new String[] { "https://example.invalid/image.svg", "http://example.invalid/image.svg", "file:///etc/passwd" }) {
			org.junit.jupiter.api.Assertions.assertThrows(SecurityException.class,
					() -> userAgent.checkLoadExternalResource(new org.apache.batik.util.ParsedURL(uri), documentUrl));
		}
		org.junit.jupiter.api.Assertions.assertThrows(SecurityException.class,
				() -> userAgent.checkLoadScript("application/ecmascript", documentUrl, documentUrl));
	}

	@ParameterizedTest
	@ValueSource(strings = { "textbox-table", "textbox-block" })
	@EnabledIfSystemProperty(named = "html2pdf.textbox-diagnostic", matches = "true")
	void rendersTextboxWithAccessibility(String fixture) throws Exception {
		File tempDirectory = (File) ReflectionTestUtils.getField(controller, "tempPathFile");
		assertNotNull(tempDirectory);
		String wikiId = "textbox-diagnostic-" + UUID.randomUUID();
		Path wikiDirectory = tempDirectory.toPath().resolve(wikiId);
		Path documentDirectory = wikiDirectory.resolve(fixture);
		try {
			Files.createDirectories(documentDirectory);
			Files.copy(Path.of("manual-testing", "data", fixture, fixture + ".html"),
					documentDirectory.resolve(fixture + ".html"));
			MockHttpServletResponse response = new MockHttpServletResponse();
			controller.renderPDF(wikiId, fixture, false, response);
			assertEquals(200, response.getStatus(),
					fixture + " failed: " + response.getErrorMessage() + "; inspect the renderer stack trace");
			assertEquals("application/pdf", response.getContentType());
			org.junit.jupiter.api.Assertions.assertTrue(response.getContentAsByteArray().length > 0);
			assertEquals(controller.index().get("buildId"), response.getHeader("X-Html2PDF-Build-Id"));
			try (PDDocument pdf = Loader.loadPDF(response.getContentAsByteArray())) {
				String producer = pdf.getDocumentInformation().getProducer();
				assertTrue(producer.contains("Html2PDF " + controller.index().get("version")));
				assertTrue(producer.contains("build=" + controller.index().get("buildId")));
				assertTrue(producer.contains("built=" + controller.index().get("buildTime")));
			}
		} finally {
			FileSystemUtils.deleteRecursively(wikiDirectory);
		}
	}

	@ParameterizedTest
	@ValueSource(strings = { "original", "empty-thead", "tbody-header" })
	@EnabledIfSystemProperty(named = "html2pdf.document-diagnostic", matches = ".+")
	void rendersCapturedDocumentWithAccessibility(String variant) throws Exception {
		Path source = Path.of(System.getProperty("html2pdf.document-diagnostic")).toAbsolutePath();
		org.junit.jupiter.api.Assertions.assertTrue(Files.isRegularFile(source), "Missing captured HTML: " + source);
		File tempDirectory = (File) ReflectionTestUtils.getField(controller, "tempPathFile");
		assertNotNull(tempDirectory);
		String wikiId = "document-diagnostic-" + UUID.randomUUID();
		String token = "captured-" + variant;
		Path wikiDirectory = tempDirectory.toPath().resolve(wikiId);
		Path documentDirectory = wikiDirectory.resolve(token);
		try {
			Files.createDirectories(documentDirectory);
			Path document = documentDirectory.resolve(token + ".html");
			if ( "original".equals( variant ) ) {
				Files.copy(source, document);
			} else {
				org.jsoup.nodes.Document parsed = Jsoup.parse(Files.readString(source),
						source.toUri().toString(), Parser.xmlParser());
				if ( "empty-thead".equals( variant ) || "tbody-header".equals( variant ) ) {
					org.jsoup.nodes.Element table = parsed.selectFirst( "table[orig-id=mwFw]" );
					org.jsoup.nodes.Element thead = table.selectFirst( "thead" );
					org.jsoup.nodes.Element tbody = table.selectFirst( "tbody" );
					org.jsoup.nodes.Element headerRow = thead.selectFirst( "tr" );
					headerRow.remove();
					tbody.prependChild( headerRow );
					if ( "tbody-header".equals( variant ) ) {
						thead.remove();
					}
				}
				Files.writeString(document, parsed.outerHtml());
			}
			for (String assetType : new String[] { "stylesheets", "images", "attachments" }) {
				Path assets = source.getParent().resolve(assetType);
				if (Files.isDirectory(assets)) {
					FileSystemUtils.copyRecursively(assets, documentDirectory.resolve(assetType));
				} else {
					System.out.println("Diagnostic: asset directory not supplied: " + assets);
				}
			}
			MockHttpServletResponse response = new MockHttpServletResponse();
			controller.renderPDF(wikiId, token, false, response);
			assertEquals(200, response.getStatus(),
					token + " failed: " + response.getErrorMessage() + "; inspect the renderer stack trace");
			assertEquals("application/pdf", response.getContentType());
			org.junit.jupiter.api.Assertions.assertTrue(response.getContentAsByteArray().length > 0);
			if ( "original".equals( variant ) ) {
				try ( PDDocument pdf = Loader.loadPDF( response.getContentAsByteArray() ) ) {
					PDStructureNode structureRoot = pdf.getDocumentCatalog().getStructureTreeRoot();
					assertNotNull( structureRoot, "PDF/UA structure tree should be present" );
					assertTrue( containsStructureType( structureRoot, "THead" ), "Table header structure should be retained" );
					assertTrue( containsStructureType( structureRoot, "TH" ), "Table header cells should be retained" );
				}
			}
		} finally {
			FileSystemUtils.deleteRecursively(wikiDirectory);
		}
	}

	private static boolean containsStructureType( PDStructureNode node, String structureType ) {
		for ( Object kid : node.getKids() ) {
			if ( kid instanceof PDStructureElement element ) {
				if ( structureType.equals( element.getStructureType() )
						|| containsStructureType( element, structureType ) ) {
					return true;
				}
			}
		}
		return false;
	}

}
