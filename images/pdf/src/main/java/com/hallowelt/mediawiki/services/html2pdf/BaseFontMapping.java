package com.hallowelt.mediawiki.services.html2pdf;

import com.openhtmltopdf.extend.FSSupplier;
import com.openhtmltopdf.pdfboxout.PdfRendererBuilder;

import java.io.InputStream;
import java.util.Map;

public class BaseFontMapping {

	public static final Map<String, String> BASE_14_TO_TTF = Map.ofEntries(
		// Times (Nimbus Roman)
		Map.entry("Times",              "/fonts/NimbusRoman-Regular.ttf"),
		Map.entry("Times-Bold",         "/fonts/NimbusRoman-Bold.ttf"),
		Map.entry("Times-Italic",       "/fonts/NimbusRoman-Italic.ttf"),
		Map.entry("Times-BoldItalic",   "/fonts/NimbusRoman-BoldItalic.ttf"),

		// Helvetica (Nimbus Sans)
		Map.entry("Helvetica",          "/fonts/NimbusSans-Regular.ttf"),
		Map.entry("Helvetica-Bold",     "/fonts/NimbusSans-Bold.ttf"),
		Map.entry("Helvetica-Oblique",  "/fonts/NimbusSans-Italic.ttf"),
		Map.entry("Helvetica-BoldOblique", "/fonts/NimbusSans-BoldItalic.ttf"),

		// Courier (Nimbus Mono)
		Map.entry("Courier",            "/fonts/NimbusMonoPS-Regular.ttf"),
		Map.entry("Courier-Bold",       "/fonts/NimbusMonoPS-Bold.ttf"),
		Map.entry("Courier-Oblique",    "/fonts/NimbusMonoPS-Italic.ttf"),
		Map.entry("Courier-BoldOblique","/fonts/NimbusMonoPS-BoldItalic.ttf"),

		// Generic fallbacks
		Map.entry("serif",              "/fonts/NimbusRoman-Regular.ttf"),
		Map.entry("sans-serif",         "/fonts/NimbusSans-Regular.ttf"),
		Map.entry("monospace",          "/fonts/NimbusMonoPS-Regular.ttf"),

		// Symbol & Dingbats
		Map.entry("Symbol",             "/fonts/StandardSymbolsPS.ttf"),
		Map.entry("ZapfDingbats",       "/fonts/D050000L.ttf")
	);

	/**
	 * Registers the Base 14 fonts for OpenHTMLtoPDF using URW TTF fonts.
	 */
	public static void registerFonts(PdfRendererBuilder builder) {
		BASE_14_TO_TTF.forEach((base14Name, fontPath) -> {
			FSSupplier<InputStream> supplier = () ->
				BaseFontMapping.class.getResourceAsStream(fontPath);
			builder.useFont(supplier, base14Name);
		});
	}
}
