package com.hallowelt.mediawiki.services.html2pdf;

import java.io.File;
import java.io.IOException;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Collection;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.Callable;
import java.util.concurrent.ConcurrentHashMap;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.jsoup.Jsoup;
import org.jsoup.helper.W3CDom;
import org.jsoup.parser.Parser;
import org.jsoup.select.Elements;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;
import org.w3c.dom.Document;

import com.openhtmltopdf.extend.FSCacheEx;
import com.openhtmltopdf.extend.FSCacheValue;
import com.openhtmltopdf.outputdevice.helper.ExternalResourceControlPriority;
import com.openhtmltopdf.outputdevice.helper.ExternalResourceType;
import com.openhtmltopdf.pdfboxout.PdfRendererBuilder;
import com.openhtmltopdf.pdfboxout.PdfRendererBuilder.CacheStore;
import com.openhtmltopdf.svgsupport.BatikSVGDrawer;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.servlet.http.Part;

@RestController
public class MainController {

	private File tempPathFile = null;

	private FallbackFontMapping fallbackFontMapping = new FallbackFontMapping();

	/**
	 * Persistent font metrics cache shared across all PDF render requests.
	 * Populated by the static classpath fonts (Base 14 and Noto fallback fonts).
	 * User-uploaded fonts referenced via CSS {@code @font-face} do not interact
	 * with this cache and are therefore never stored here.
	 */
	private final FSCacheEx<String, FSCacheValue> fontMetricsCache = new FSCacheEx<String, FSCacheValue>() {
		private final ConcurrentHashMap<String, FSCacheValue> store = new ConcurrentHashMap<>();

		@Override
		public void put(String key, FSCacheValue value) {
			store.put(key, value);
		}

		@Override
		public FSCacheValue get(String key, Callable<? extends FSCacheValue> loader) {
			FSCacheValue cached = store.get(key);
			if (cached != null) {
				return cached;
			}
			try {
				FSCacheValue value = loader.call();
				if (value != null) {
					store.put(key, value);
				}
				return value;
			} catch (Exception e) {
				return null;
			}
		}

		@Override
		public FSCacheValue get(String key) {
			return store.get(key);
		}
	};

	private static final Logger logger = LoggerFactory.getLogger(MainController.class);

	/**
	 * Matches either a complete CSS {@code @font-face} block (group 1) or a
	 * {@code font-family}/{@code font} shorthand declaration outside such a block,
	 * capturing the property prefix (group 2) and the value (group 3).
	 *
	 * <p>The {@code @font-face} alternative is listed first so that any
	 * {@code font-family} property inside a {@code @font-face} rule is consumed by
	 * group 1 and is therefore never seen by the rewriting logic.
	 *
	 * <p>The property pattern {@code font(?:-family)?} matches {@code font} and
	 * {@code font-family} but never {@code font-size}, {@code font-weight}, etc.,
	 * because those properties have a further {@code -} suffix that prevents the
	 * trailing {@code \\s*:} from matching.
	 */
	private static final Pattern FONT_DECLARATION_PATTERN = Pattern.compile(
		"(@font-face\\s*\\{[^}]*\\})|(font(?:-family)?\\s*:\\s*)([^;}]+)",
		Pattern.CASE_INSENSITIVE
	);

	/**
	 * Matches the five CSS generic font-family keywords as whole tokens, guarded by
	 * negative look-behind/ahead so that:
	 * <ul>
	 *   <li>{@code serif} inside {@code sans-serif} is not matched (the preceding
	 *       {@code -} triggers the look-behind);</li>
	 *   <li>already-rewritten values (e.g. {@code sans-serif-fallback}) are not
	 *       matched again (negative look-ahead {@code (?!-fallback)}).</li>
	 * </ul>
	 * {@code sans-serif} must appear before {@code serif} in the alternation so the
	 * longer token is tried first.
	 */
	private static final Pattern GENERIC_FONT_FAMILY_PATTERN = Pattern.compile(
		"(?<![a-zA-Z0-9-])(sans-serif|serif|monospace|cursive|fantasy)(?!-fallback)(?![a-zA-Z0-9-])",
		Pattern.CASE_INSENSITIVE
	);

	public MainController() {
		String tempPath = System.getProperty("html2pdf.temp.dir");
		if (tempPath == null) {
			tempPath = System.getenv("HTML2PDF_TEMP_DIR");
		}
		if (tempPath == null) {
			tempPath = System.getProperty("java.io.tmpdir");
			tempPath = tempPath + "/html2pdf";
		}

		tempPathFile = new File(tempPath);
		if (!tempPathFile.exists()) {
			tempPathFile.mkdirs();
		}
	}

	@GetMapping("/")
	public Map<String, Object> index() {
		Map<String, Object> response = new HashMap<>();
		response.put("success", true);
		response.put("msg", "Service is running");
		response.put( "version", "2.0.0");
		return response;
	}

	// Returns application/pdf
	@PostMapping("/RenderPDF")
	public void renderPDF(
			@RequestParam("wikiId") String wikiId,
			@RequestParam("documentToken") String documentToken,
			@RequestParam(name = "debug", required = false, defaultValue = "false") boolean debug,
			HttpServletResponse response) {
		logger.info(String.format(
				"Start creating PDF for (%s/%s)", wikiId, documentToken));

		try {
			File wikiIdPath = new File(tempPathFile, wikiId);
			File basePathFile = new File(wikiIdPath, documentToken);
			File documentFile = new File(basePathFile, documentToken + ".html");

			PdfRendererBuilder builder = new PdfRendererBuilder();

			builder.useCacheStore(CacheStore.PDF_FONT_METRICS, fontMetricsCache);

			BaseFontMapping.registerFonts(builder);
			fallbackFontMapping.registerFallbackFonts(builder);

			builder.useFastMode();
			builder.usePdfUaAccessibility(true);
			builder.usePdfAConformance(PdfRendererBuilder.PdfAConformance.PDFA_3_U);
			builder.useSVGDrawer(new BatikSVGDrawer());
			builder.useExternalResourceAccessControl(
				(uri, type) -> {
					return this.allowFileEmbed(uri, type);
				},
				ExternalResourceControlPriority.RUN_AFTER_RESOLVING_URI);
			builder.useExternalResourceAccessControl(
				(uri, type) -> {
					return this.allowFileEmbed(uri, type);
				},
				ExternalResourceControlPriority.RUN_BEFORE_RESOLVING_URI);

			logger.info("Document File: " + documentFile.getAbsolutePath());
			Document doc = html5ParseDocument(documentFile);
			builder.withW3cDocument(doc, documentFile.toURI().toASCIIString());

			logger.debug("Start building PDF");
			builder.toStream(response.getOutputStream());
			builder.run();
			logger.debug("Done building PDF");

			response.setContentType("application/pdf");
			response.setHeader("Content-Disposition", "attachment; filename=\"" + documentToken + ".pdf\"");
			response.setHeader("Cache-Control", "no-cache");
			response.setHeader("Pragma", "no-cache");
			response.setHeader("Expires", "0");

			if (!debug) {
				deleteDirectory(basePathFile);
			}
		} catch (Exception e) {
			logger.error("Error creating PDF", e);
		}
	}

	private Document html5ParseDocument(File htmlFile) throws IOException {
		String fileContents = org.apache.commons.io.FileUtils.readFileToString(htmlFile, "UTF-8");
		org.jsoup.nodes.Document doc = Jsoup.parse(
			fileContents,
			htmlFile.toURI().toASCIIString(),
			Parser.xmlParser() // Required as otherwise CDATA is not parsed correctly
		);

		this.sanitizeDocument(doc);

		// Find all elements that have `data-fs-embed-file="true"`and convert to
		// `download="..."`
		// https://github.com/danfickle/openhtmltopdf/wiki/Embedding-downloadable-files#html
		Elements embeds = doc.select("[data-fs-embed-file=true]");
		for (org.jsoup.nodes.Element embed : embeds) {
			logger.debug("Original Element: " + embed.toString());

			String href = embed.attr("href");
			if (href.isEmpty()) {
				continue;
			}

			String mimeType = "application/octet-stream";
			if (href.endsWith(".pdf")) {
				mimeType = "application/pdf";
			} else if (href.endsWith(".html")) {
				mimeType = "text/html";
			} else if (href.endsWith(".png")) {
				mimeType = "image/png";
			} else if (href.endsWith(".jpg") || href.endsWith(".jpeg")) {
				mimeType = "image/jpeg";
			} else if (href.endsWith(".gif")) {
				mimeType = "image/gif";
			} else if (href.endsWith(".svg")) {
				mimeType = "image/svg+xml";
			} else if (href.endsWith(".css")) {
				mimeType = "text/css";
			} else if (href.endsWith(".js")) {
				mimeType = "application/javascript";
			} else if (href.endsWith(".json")) {
				mimeType = "application/json";
			} else if (href.endsWith(".xml")) {
				mimeType = "application/xml";
			} else if (href.endsWith(".txt")) {
				mimeType = "text/plain";
			} else if (href.endsWith(".zip")) {
				mimeType = "application/zip";
			}
			// MICROSOFT OFFICE FILES
			else if (href.endsWith(".doc")) {
				mimeType = "application/msword";
			} else if (href.endsWith(".docx")) {
				mimeType = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
			} else if (href.endsWith(".xls")) {
				mimeType = "application/vnd.ms-excel";
			} else if (href.endsWith(".xlsx")) {
				mimeType = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
			} else if (href.endsWith(".ppt")) {
				mimeType = "application/vnd.ms-powerpoint";
			} else if (href.endsWith(".pptx")) {
				mimeType = "application/vnd.openxmlformats-officedocument.presentationml.presentation";
			}

			String fileName = href.substring(href.lastIndexOf('/') + 1);
			embed.attr("download", fileName);
			embed.attr("data-content-type", mimeType);
			embed.attr("relationship", "Source");
			embed.removeAttr("style");

			logger.debug("Modified Element: " + embed.toString());
		}

		return new W3CDom().fromJsoup(doc);
	}

	private boolean allowFileEmbed(String uri, ExternalResourceType type) {
		if (type == null) {
			return false;
		}
		switch (type) {
			case BINARY:
			case CSS:
			case FONT:
			case IMAGE_RASTER:
			case XML_XHTML:
			case XML_SVG:
			case PDF:
			case SVG_BINARY:
			case FILE_EMBED:
				return true;
		}
		return false;
	}

	/**
	 * Sanitizes the document by removing elements that breaking PDF rendering.
	 *
	 * @param doc The document to sanitize.
	 */
	private void sanitizeDocument(org.jsoup.nodes.Document doc) {
		Elements inputs = doc.select("input");
		for (org.jsoup.nodes.Element input : inputs) {
			logger.debug("Sanitize: replace element: " + input.toString());

			org.jsoup.nodes.Element span = new org.jsoup.nodes.Element("span");

			String value = input.attr("value");
			if (!value.isEmpty()) {
				span.text(value);
			}

			if (input.hasAttr("class")) {
				span.attr("class", input.attr("class"));
			}
			if (input.hasAttr("style")) {
				span.attr("style", input.attr("style"));
			}

			// Replace the input with the span
			input.replaceWith(span);
		}

		// We need to strip all unsupported font-families from inline CSS styles
		Elements styledElements = doc.select("[style]");
		for (org.jsoup.nodes.Element el : styledElements) {
			String style = el.attr("style");
			String normalizedStyle = style
				.replaceAll(";;+", ";") // multiple semicolons
				.replaceAll(";+$", "") // trailing semicolons
				.replaceAll("\\s+", " ") // multiple spaces
				.trim();

			// Examples of things to strip
			// * font-family:Wingdings;mso-fareast-font-family:Wingdings;mso-bidi-font-family:Wingdings
			// * mso-list:Ignore
			// * font:7.0pt "Times New Roman"
			String[] parts = normalizedStyle.split(";");
			List<String> sanitizedParts = new ArrayList<>();
			for (int i = 0; i < parts.length; i++) {
				String part = parts[i];
				String[] rule = part.split(":", 2);
				if (rule.length != 2) {
					continue;
				}
				String property = rule[0].trim();
				String normalizedProperty = property.toLowerCase();
				String value = rule[1].trim();
				String normalizedValue = value.toLowerCase();

				if (normalizedProperty.equals("font-family")
					||normalizedProperty.equals("font")
					||normalizedProperty.startsWith("mso-")) {
					logger.debug("Sanitize: remove property: " + normalizedProperty + ":" + normalizedValue);
					continue;
				}

				sanitizedParts.add(part);
			}

			String sanitizedStyle = String.join(";", sanitizedParts);
			if (!normalizedStyle.equals(sanitizedStyle)) {
				logger.debug("Sanitize: update style: " + style + " => " + sanitizedStyle);
				el.attr("style", sanitizedStyle);
			}
		}

		Elements styleElements = doc.select("style");
		for (org.jsoup.nodes.Element el : styleElements) {
			String css = el.text();
			String rewrittenCss = rewriteGenericFontFamilies(css);
			if (!css.equals(rewrittenCss)) {
				logger.debug("Sanitize: update font-family in style element");
				el.text(rewrittenCss);
			}
		}

		doc.select("head").prepend("<style>html{font-family:serif-fallback," + fallbackFontMapping.getFontFamilyNames() + "}</style>");
	}

	/**
	 * Rewrites the five CSS generic font-family keywords ({@code serif},
	 * {@code sans-serif}, {@code monospace}, {@code cursive}, {@code fantasy}) to
	 * their {@code -fallback}-suffixed counterparts within {@code font-family} and
	 * {@code font} shorthand declarations found in {@code content}.
	 * {@code fallbackFontFamilies} is
	 * appended to the end of all found values so that the full Noto fallback chain is
	 * available for any characters not covered by the named fonts.
	 *
	 * <p>The replacement is scoped to declaration values only, so occurrences inside
	 * URL paths, comments, or other CSS constructs are left untouched.
	 * {@code @font-face} blocks are passed through unchanged — the {@code font-family}
	 * property inside them declares a font name rather than selecting a font, so it
	 * must never be rewritten.
	 * Within {@code font} shorthand values the keyword pattern only matches the five
	 * specific generic family tokens — not font-weight keywords, sizes, etc. — so
	 * co-existing values like {@code bold} or {@code 14px} are preserved as-is.
	 *
	 * @param content raw CSS (or any text containing CSS declarations)
	 * @return the content with generic font-family keywords postfixed by
	 *         {@code -fallback} and the fallback chain appended
	 */
	private String rewriteGenericFontFamilies(String content) {
		Matcher m = FONT_DECLARATION_PATTERN.matcher(content);
		StringBuffer sb = new StringBuffer();
		String fallbackFontFamilies = fallbackFontMapping.getFontFamilyNames();
		while (m.find()) {
			if (m.group(1) != null) {
				// @font-face block — keep entirely unchanged
				m.appendReplacement(sb, Matcher.quoteReplacement(m.group(1)));
			} else {
				String prefix = m.group(2);
				String value = m.group(3);
				String rewrittenValue = GENERIC_FONT_FAMILY_PATTERN.matcher(value).replaceAll("$1-fallback");
				if (!fallbackFontFamilies.isEmpty()) {
					rewrittenValue = rewrittenValue.stripTrailing() + ", " + fallbackFontFamilies;
				}
				m.appendReplacement(sb, Matcher.quoteReplacement(prefix + rewrittenValue));
			}
		}
		m.appendTail(sb);
		return sb.toString();
	}

	private void deleteDirectory(File directroy) {
		if (directroy.exists()) {
			File[] files = directroy.listFiles();
			if (files != null) {
				for (File file : files) {
					if (file.isDirectory()) {
						deleteDirectory(file);
					} else {
						file.delete();
					}
				}
			}
			directroy.delete();
		}
	}

	@PostMapping("/UploadAsset")
	public Map<String, Object> uploadAsset(HttpServletRequest request) {
		Map<String, Object> response = new HashMap<>();
		List<Object> filesArray = new ArrayList<>();

		response.put("success", false);
		response.put("msg", "No multipart content provided");

		if (request.getContentType() == null || !request.getContentType().startsWith("multipart/form-data")) {
			return response;
		}

		String wikiId = request.getParameter("wikiId");
		String documentToken = request.getParameter("documentToken");
		String type = request.getParameter("fileType");

		if (wikiId == null || documentToken == null || type == null) {
			response.put("msg", "Missing parameters");
			logger.error("Missing parameters: " + request.getParameterMap());
			return response;
		}

		logger.info(String.format(
				"Uploading asset for (%s/%s/%s)", wikiId, documentToken, type));
		if (wikiId.contains("..") || documentToken.contains("..") || type.contains("..")) {
			response.put("msg", "Invalid path");
			return response;
		}
		File wikiIdPath = new File(tempPathFile, wikiId);
		File documentPath = new File(wikiIdPath, documentToken);
		File typePath = new File(documentPath, type);

		if (!typePath.exists()) {
			logger.debug("Creating directory: " + typePath.getAbsolutePath());
			typePath.mkdirs();
		}

		List<String> nonFileFields = Arrays.asList("wikiId", "documentToken", "type");

		try {
			Collection<Part> parts = request.getParts();
			parts.forEach(part -> {

				String fieldName = part.getName();
				if (nonFileFields.contains(fieldName)) {
					return;
				}

				String submittedFileName = part.getSubmittedFileName();
				if (submittedFileName == null || submittedFileName.isEmpty()) {
					return;
				}

				Integer size = (int) part.getSize();

				logger.info(String.format(
						"Retrieving file: %s = %s (Size: %s)", fieldName, submittedFileName, size));

				Map<String, Object> fileObject = new HashMap<>();
				File fileToSave = new File(typePath, submittedFileName);
				try {
					if (submittedFileName.toLowerCase().endsWith(".css")) {
						String cssContent = org.apache.commons.io.IOUtils.toString(part.getInputStream(), "UTF-8");
						String rewrittenContent = rewriteGenericFontFamilies(cssContent);
						org.apache.commons.io.FileUtils.writeStringToFile(fileToSave, rewrittenContent, "UTF-8");
						logger.info("Saved CSS with rewritten generic font families: " + fileToSave.getAbsolutePath());
					} else {
						part.write(fileToSave.getAbsolutePath());
					}
					fileObject.put("fieldName", fieldName);
					fileObject.put("fileName", submittedFileName);
					fileObject.put("contentType", part.getContentType());
					fileObject.put("size", size);
					filesArray.add(fileObject);
					logger.debug("File saved: " + fileToSave.getAbsolutePath());
				} catch (Exception e) {
					logger.error("Error saving file", e);
				}
			});
		} catch (Exception e) {
			response.put("msg", e.getMessage());
			return response;
		}
		response.put("success", true);
		response.remove("msg");
		response.put("files", filesArray);
		return response;
	}

}