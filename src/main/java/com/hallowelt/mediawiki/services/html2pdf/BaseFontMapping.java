package com.hallowelt.mediawiki.services.html2pdf;

import com.openhtmltopdf.outputdevice.helper.BaseRendererBuilder.FontStyle;
import com.openhtmltopdf.pdfboxout.PdfRendererBuilder;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class BaseFontMapping {

	/**
	 * Structured font entry associating a PDF/CSS font family name with the
	 * corresponding classpath resource and the SVG rendering metadata (base
	 * family name, weight, style) needed to build {@code @font-face} rules.
	 *
	 * <p>{@code svgFamily} is the root family name an SVG author would write in
	 * a {@code font-family} attribute (e.g. {@code "Helvetica"}), while
	 * {@code cssName} is the exact name registered with the PDF renderer
	 * (e.g. {@code "Helvetica-Bold"}).  They differ for weight/style variants
	 * and are equal for the base entry of each family and for all fallback fonts.
	 */
	public static class FontInfo {
		public final String cssName;
		public final String svgFamily;
		public final int weight;
		public final FontStyle style;
		public final String resourcePath;

		public FontInfo(String cssName, String svgFamily, int weight, FontStyle style, String resourcePath) {
			this.cssName = cssName;
			this.svgFamily = svgFamily;
			this.weight = weight;
			this.style = style;
			this.resourcePath = resourcePath;
		}

		/**
		 * Returns the CSS name with single-quotes escaped, suitable for use
		 * inside a single-quoted CSS string (e.g. a {@code font-family} value).
		 */
		public String cssNameSingleQuoteEscaped() {
			return cssName.replace("'", "\\'");
		}
	}

	/**
	 * Ordered list of all Base-14 font entries plus the generic-family
	 * fallbacks and the Symbol/Dingbats faces.  The list drives both PDF font
	 * registration and SVG {@code @font-face} generation.
	 */
	public static final List<FontInfo> FONT_ENTRIES = List.of(
		// Times (Nimbus Roman)
		new FontInfo("Times",              "Times", 400, FontStyle.NORMAL, "/fonts/NimbusRoman-Regular.ttf"),
		new FontInfo("Times-Bold",         "Times", 700, FontStyle.NORMAL, "/fonts/NimbusRoman-Bold.ttf"),
		new FontInfo("Times-Italic",       "Times", 400, FontStyle.ITALIC, "/fonts/NimbusRoman-Italic.ttf"),
		new FontInfo("Times-BoldItalic",   "Times", 700, FontStyle.ITALIC, "/fonts/NimbusRoman-BoldItalic.ttf"),

		// Helvetica (Nimbus Sans)
		new FontInfo("Helvetica",             "Helvetica", 400, FontStyle.NORMAL, "/fonts/NimbusSans-Regular.ttf"),
		new FontInfo("Helvetica-Bold",        "Helvetica", 700, FontStyle.NORMAL, "/fonts/NimbusSans-Bold.ttf"),
		new FontInfo("Helvetica-Oblique",     "Helvetica", 400, FontStyle.ITALIC, "/fonts/NimbusSans-Italic.ttf"),
		new FontInfo("Helvetica-BoldOblique", "Helvetica", 700, FontStyle.ITALIC, "/fonts/NimbusSans-BoldItalic.ttf"),

		// Courier (Nimbus Mono)
		new FontInfo("Courier",             "Courier", 400, FontStyle.NORMAL, "/fonts/NimbusMonoPS-Regular.ttf"),
		new FontInfo("Courier-Bold",        "Courier", 700, FontStyle.NORMAL, "/fonts/NimbusMonoPS-Bold.ttf"),
		new FontInfo("Courier-Oblique",     "Courier", 400, FontStyle.ITALIC, "/fonts/NimbusMonoPS-Italic.ttf"),
		new FontInfo("Courier-BoldOblique", "Courier", 700, FontStyle.ITALIC, "/fonts/NimbusMonoPS-BoldItalic.ttf"),

		// Generic fallbacks — the generic names `serif` etc. are rewritten to
		// their `-fallback` variants in HTML/CSS to enforce PDF/A font embedding.
		new FontInfo("serif-fallback",      "serif-fallback",      400, FontStyle.NORMAL, "/fonts/NimbusRoman-Regular.ttf"),
		new FontInfo("sans-serif-fallback", "sans-serif-fallback", 400, FontStyle.NORMAL, "/fonts/NimbusSans-Regular.ttf"),
		new FontInfo("monospace-fallback",  "monospace-fallback",  400, FontStyle.NORMAL, "/fonts/NimbusMonoPS-Regular.ttf"),

		// Symbol & Dingbats
		new FontInfo("Symbol",      "Symbol",      400, FontStyle.NORMAL, "/fonts/StandardSymbolsPS.ttf"),
		new FontInfo("ZapfDingbats", "ZapfDingbats", 400, FontStyle.NORMAL, "/fonts/D050000L.ttf")
	);

	/**
	 * Legacy map derived from {@link #FONT_ENTRIES} — kept for the CSS
	 * sanitisation logic in {@code MainController} that checks whether a
	 * declared font-family is one of the known base fonts.
	 */
	public static final Map<String, String> BASE_14_TO_TTF;

	static {
		Map<String, String> m = new LinkedHashMap<>();
		for (FontInfo fi : FONT_ENTRIES) {
			m.put(fi.cssName, fi.resourcePath);
		}
		BASE_14_TO_TTF = Map.copyOf(m);
	}

	/**
	 * Registers the Base-14 fonts with the PDF renderer builder so that they
	 * are embedded in the output PDF.
	 *
	 * <p>Note: font availability is also covered by the {@code @font-face} CSS
	 * injected via {@link #extractFontsAndGenerateCSS}, which feeds both the
	 * PDF font resolver and Batik's SVG font resolver from the same rule set.
	 * This method is therefore technically redundant, but kept as a resilience
	 * layer: if font file extraction fails at startup the classpath-based
	 * suppliers registered here ensure that HTML text still renders correctly
	 * under PDF/A even when the disk-backed {@code @font-face} rules are
	 * unavailable.
	 */
	public static void registerFonts(PdfRendererBuilder builder) {
		for (FontInfo fi : FONT_ENTRIES) {
			builder.useFont(
				() -> BaseFontMapping.class.getResourceAsStream(fi.resourcePath),
				fi.cssName, fi.weight, fi.style, true);
		}
	}

	/**
	 * Extracts every Base-14 font resource to {@code fontsDir} (creating it if
	 * necessary) and returns a CSS snippet of {@code @font-face} rules that
	 * make those fonts available to Apache Batik's SVG renderer.
	 *
	 * <h3>Two kinds of rules are generated per font entry</h3>
	 * <ol>
	 *   <li><strong>Exact-name rule</strong> – registered under the full PDF/CSS
	 *       name (e.g. {@code "Helvetica-Bold"}) with explicit
	 *       {@code font-weight} and {@code font-style} descriptors.  This lets
	 *       SVG {@code <text font-family="Helvetica-Bold">} resolve to the
	 *       correct file.</li>
	 *   <li><strong>SVG root-family rule</strong> – registered under the root
	 *       SVG family name (e.g. {@code "Helvetica"}) for the <em>regular
	 *       (weight 400, style normal) variant only</em>, and <em>without</em>
	 *       {@code font-weight} or {@code font-style} descriptors.  Omitting
	 *       those descriptors means {@code importFontFaces} stores the font
	 *       under the key {@code {weight=null, style=null}}.  When Batik renders
	 *       {@code <text font-family="Helvetica">} with no explicit weight or
	 *       style it calls {@code deriveFont} with the same {@code {null, null}}
	 *       key, producing an exact match and returning the correct regular
	 *       font.  Registering only one entry per root family also prevents the
	 *       random-fallback behaviour in
	 *       {@code OpenHtmlGvtFontFamily.deriveFont} that would otherwise occur
	 *       when multiple weight/style variants are present and none matches the
	 *       key supplied by Batik.</li>
	 * </ol>
	 *
	 * <p>Font files are only written once; subsequent calls with the same
	 * directory are cheap.
	 *
	 * @param fontsDir directory into which font files are extracted
	 * @return CSS text ready to be injected inside a {@code <style>} element
	 * @throws IOException if a font resource cannot be read or written
	 */
	public static String extractFontsAndGenerateCSS(File fontsDir) throws IOException {
		fontsDir.mkdirs();
		StringBuilder css = new StringBuilder();

		// Track which SVG root-family aliases have already been generated so
		// that only the regular variant (the first match for each family) is
		// written.
		java.util.Set<String> svgFamilyAliasesWritten = new java.util.HashSet<>();

		for (FontInfo fi : FONT_ENTRIES) {
			String fileName = new File(fi.resourcePath).getName();
			File outFile = new File(fontsDir, fileName);

			if (!outFile.exists()) {
				try (InputStream is = BaseFontMapping.class.getResourceAsStream(fi.resourcePath);
					 FileOutputStream fos = new FileOutputStream(outFile)) {
					if (is == null) {
						throw new IOException("Font resource not found on classpath: " + fi.resourcePath);
					}
					is.transferTo(fos);
				}
			}

			String fileUrl = outFile.toURI().toASCIIString();
			String weightStr = fi.weight >= 700 ? "bold" : "normal";
			String styleStr  = fi.style == FontStyle.ITALIC || fi.style == FontStyle.OBLIQUE ? "italic" : "normal";

			// Exact-name rule — explicit weight and style so the font can be
			// found when the SVG references the full PDF/CSS name directly
			// (e.g. font-family="Helvetica-Bold").
			appendFontFaceWithDescriptors(css, fi.cssName, weightStr, styleStr, fileUrl);

			// SVG root-family alias rule — only for the regular (non-bold,
			// non-italic) variant, and without weight/style descriptors.
			//
			// Rationale: importFontFaces stores fonts keyed by {weight, style}
			// where null means "not specified".  Batik calls deriveFont with
			// {null, null} for text that carries no explicit weight/style.
			// Omitting the descriptors causes both the storage key and the
			// lookup key to be {null, null}, producing an exact match.
			// Registering only one entry also prevents the random iterator
			// fallback that would otherwise fire when multiple variants are
			// present and none matches the supplied key.
			boolean isRegular = fi.weight == 400
				&& fi.style != FontStyle.ITALIC
				&& fi.style != FontStyle.OBLIQUE;
			if (isRegular && !fi.svgFamily.equals(fi.cssName)
					&& svgFamilyAliasesWritten.add(fi.svgFamily)) {
				appendFontFaceNoDescriptors(css, fi.svgFamily, fileUrl);
			}
		}

		return css.toString();
	}

	private static void appendFontFaceWithDescriptors(
			StringBuilder css, String family, String weight, String style, String fileUrl) {
		css.append("@font-face{font-family:\"")
		   .append(family.replace("\"", "\\\""))
		   .append("\";font-weight:").append(weight)
		   .append(";font-style:").append(style)
		   .append(";src:url(\"").append(fileUrl).append("\");}");
	}

	private static void appendFontFaceNoDescriptors(
			StringBuilder css, String family, String fileUrl) {
		css.append("@font-face{font-family:\"")
		   .append(family.replace("\"", "\\\""))
		   .append("\";src:url(\"").append(fileUrl).append("\");}");
	}
}
