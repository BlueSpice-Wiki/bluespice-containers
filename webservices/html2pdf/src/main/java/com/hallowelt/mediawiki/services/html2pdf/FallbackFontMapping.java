package com.hallowelt.mediawiki.services.html2pdf;

import java.io.InputStream;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Set;
import java.util.stream.Collectors;

import java.awt.Font;
import java.awt.FontFormatException;

import com.openhtmltopdf.outputdevice.helper.BaseRendererBuilder.FontStyle;
import com.openhtmltopdf.pdfboxout.PdfBoxRawPDFontMetrics;
import com.openhtmltopdf.pdfboxout.PdfRendererBuilder;

import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.pdmodel.font.PDFont;
import org.apache.pdfbox.pdmodel.font.PDType0Font;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

public class FallbackFontMapping {

    private static final Logger logger = LoggerFactory.getLogger(FallbackFontMapping.class);

    /**
     * Key prefix used by openhtmltopdf's {@code PdfBoxFontResolver.FontDescription}
     * when it looks up font metrics in the cache configured via
     * {@code CacheStore.PDF_FONT_METRICS}.  The full key is
     * {@code font-metrics:<family>:<weight>:<style>}.
     */
    private static final String METRICS_CACHE_KEY_PREFIX = "font-metrics:";

    /**
     * Latin fallback font whose metrics are used as the reference geometry for
     * all other fallback fonts.
     */
    private static final String REFERENCE_FONT_RESOURCE = "/fonts/noto/NotoSans-Regular.ttf";

    private List<BaseFontMapping.FontInfo> fontsAdded;
    private String fontFamilyNamesCache;
    private Set<String> fallbackFamilies;
    private PdfBoxRawPDFontMetrics referenceMetrics;

    public FallbackFontMapping() {
        loadFallbackFonts();
        this.referenceMetrics = loadReferenceMetrics();
    }

    private void loadFallbackFonts() {
        /* ordered list of Noto fonts. This is deliberate, because glyphs
         * are included in several of the font files, but with differences
         * in kerning. This list sorts by script usage based on
         * https://en.wikipedia.org/wiki/Unicode_script#Script_usage_statistics,
         * with the most commonly used scripts first and a cut-off by 1 mio
         * active users. */
        List<String> orderedFileNames = List.of(
            "/fonts/noto/NotoSans-Regular.ttf",
            "/fonts/noto/NotoSansSymbols-Regular.ttf",
            "/fonts/noto/NotoSansSymbols2-Regular.ttf",
            "/fonts/noto/NotoMusic-Regular.ttf",
            "/fonts/noto/NotoEmoji-Regular.ttf",
            "/fonts/noto/NotoSansSC-VF.ttf",
            "/fonts/noto/NotoSansTC-VF.ttf",
            "/fonts/noto/NotoSansHK-VF.ttf",
            "/fonts/noto/NotoSansJP-VF.ttf",
            "/fonts/noto/NotoSansKR-VF.ttf",
            "/fonts/noto/NotoSansArabic-Regular.ttf",
            "/fonts/noto/NotoSansDevanagari-Regular.ttf",
            "/fonts/noto/NotoSansBengali-Regular.ttf",
            "/fonts/noto/NotoSansTelugu-Regular.ttf",
            "/fonts/noto/NotoSansTamil-Regular.ttf",
            "/fonts/noto/NotoSansTamilSupplement-Regular.ttf",
            "/fonts/noto/NotoSansThai-Regular.ttf",
            "/fonts/noto/NotoSansThaiLooped-Regular.ttf",
            "/fonts/noto/NotoSansJavanese-Regular.ttf",
            "/fonts/noto/NotoSansGujarati-Regular.ttf",
            "/fonts/noto/NotoSansKannada-Regular.ttf",
            "/fonts/noto/NotoSansEthiopic-Regular.ttf",
            "/fonts/noto/NotoSansMyanmar-Regular.ttf",
            "/fonts/noto/NotoSansMalayalam-Regular.ttf",
            "/fonts/noto/NotoSansTagalog-Regular.ttf",
            "/fonts/noto/NotoSansAdlam-Regular.ttf",
            "/fonts/noto/NotoSansAdlamUnjoined-Regular.ttf",
            "/fonts/noto/NotoSansOriya-Regular.ttf",
            "/fonts/noto/NotoSansTirhuta-Regular.ttf",
            "/fonts/noto/NotoSansGurmukhi-Regular.ttf",
            "/fonts/noto/NotoSansSundanese-Regular.ttf",
            "/fonts/noto/NotoSansSylotiNagri-Regular.ttf",
            "/fonts/noto/NotoSansSinhala-Regular.ttf",
            "/fonts/noto/NotoSansKhmer-Regular.ttf",
            "/fonts/noto/NotoSansCoptic-Regular.ttf",
            "/fonts/noto/NotoSansYi-Regular.ttf",
            "/fonts/noto/NotoSansHebrew-Regular.ttf",
            "/fonts/noto/NotoSansNKo-Regular.ttf",
            "/fonts/noto/NotoSansNKoUnjoined-Regular.ttf",
            "/fonts/noto/NotoSansOlChiki-Regular.ttf",
            "/fonts/noto/NotoSansBatak-Regular.ttf",
            "/fonts/noto/NotoSansTaiViet-Regular.ttf",
            "/fonts/noto/NotoSansLao-Regular.ttf",
            "/fonts/noto/NotoSansLaoLooped-Regular.ttf",
            "/fonts/noto/NotoSansTaiTham-Regular.ttf",
            "/fonts/noto/NotoSerifTibetan-Regular.ttf",
            "/fonts/noto/NotoSansArmenian-Regular.ttf",
            "/fonts/noto/NotoSansTifinagh-Regular.ttf",
            "/fonts/noto/NotoSansMongolian-Regular.ttf",
            "/fonts/noto/NotoSansSyriac-Regular.ttf",
            "/fonts/noto/NotoSansBuginese-Regular.ttf",
            "/fonts/noto/NotoSansGeorgian-Regular.ttf",
            "/fonts/noto/NotoSansBalinese-Regular.ttf",
            "/fonts/noto/NotoSansHanifiRohingya-Regular.ttf",
            "/fonts/noto/NotoSansGunjalaGondi-Regular.ttf",
            "/fonts/noto/NotoSansMasaramGondi-Regular.ttf",
            "/fonts/noto/NotoSansMeeteiMayek-Regular.ttf",
            "/fonts/noto/NotoSansNewa-Regular.ttf",
            "/fonts/noto/NotoSansWarangCiti-Regular.ttf",
            "/fonts/noto/NotoSansLisu-Regular.ttf"
        );

        List<BaseFontMapping.FontInfo> result = new ArrayList<>();
        for (String path : orderedFileNames) {
            try (InputStream is = FallbackFontMapping.class.getResourceAsStream(path)) {
                if (is == null) {
                    logger.warn("Font resource not found: {}", path);
                    continue;
                }
                Font f = Font.createFont(Font.TRUETYPE_FONT, is);
                String family = f.getFamily();
                // Short of parsing the font ourselves there doesn't seem to be a way
                // of getting the font properties, so we use heuristics based on font name.
                String name = f.getFontName(Locale.US).toLowerCase(Locale.US);
                int weight = name.contains("bold") ? 700 : 400;
                FontStyle style = name.contains("italic") ? FontStyle.ITALIC : FontStyle.NORMAL;
                // Fallback fonts are never referenced by SVG by their family name, so
                // svgFamily equals cssName.
                result.add(new BaseFontMapping.FontInfo(family, family, weight, style, path));
            } catch (IOException e) {
                logger.error("Error reading font file: {}", path, e);
            } catch (FontFormatException e) {
                logger.warn("Ignoring font file with invalid font format: {}", path, e);
            }
        }

        this.fontsAdded = result;
        this.fontFamilyNamesCache = toCSSFontFamilyList(result);
        this.fallbackFamilies = result.stream()
            .map(fi -> fi.cssName)
            .collect(Collectors.toCollection(HashSet::new));
    }

    /**
     * Reads the metrics of {@link #REFERENCE_FONT_RESOURCE} exactly the way
     * openhtmltopdf would, so that they can be substituted for the metrics of
     * the remaining fallback fonts.
     */
    private PdfBoxRawPDFontMetrics loadReferenceMetrics() {
        try (PDDocument doc = new PDDocument();
             InputStream is = FallbackFontMapping.class.getResourceAsStream(REFERENCE_FONT_RESOURCE)) {
            if (is == null) {
                logger.warn("Reference fallback font not found: {}", REFERENCE_FONT_RESOURCE);
                return null;
            }
            PDFont font = PDType0Font.load(doc, is, true);
            return PdfBoxRawPDFontMetrics.fromPdfBox(font, font.getFontDescriptor());
        } catch (IOException e) {
            logger.error("Could not read metrics of reference fallback font", e);
            return null;
        }
    }

    /**
     * Returns the metrics that should be reported for the font addressed by
     * {@code cacheKey}, or {@code null} if the key does not belong to one of the
     * fallback fonts.
     *
     * <p>openhtmltopdf sizes a line box from the <em>largest</em> ascent and
     * descent of <em>all</em> fonts in the resolved font-family chain, and it
     * derives those values from the font bounding box rather than from the typo
     * metrics.  Since the complete Noto chain is appended to every font-family,
     * scripts with tall bounding boxes (Tibetan, Devanagari, musical symbols, …)
     * would inflate every line of plain Latin text.  The visible effect is that
     * the content area of an inline box grows far beyond its line-height, so the
     * backgrounds of consecutive lines overlap and descenders get painted over.
     *
     * <p>Reporting the Latin reference metrics for every fallback font keeps the
     * glyph coverage of the chain intact while restoring Latin-sized line
     * geometry.
     */
    public PdfBoxRawPDFontMetrics getMetricsOverride(String cacheKey) {
        if (referenceMetrics == null || cacheKey == null
            || !cacheKey.startsWith(METRICS_CACHE_KEY_PREFIX)) {
            return null;
        }
        int familyEnd = cacheKey.indexOf(':', METRICS_CACHE_KEY_PREFIX.length());
        if (familyEnd < 0) {
            return null;
        }
        String family = cacheKey.substring(METRICS_CACHE_KEY_PREFIX.length(), familyEnd);
        return fallbackFamilies.contains(family) ? referenceMetrics : null;
    }

    /**
     * Returns the CSS font-family names for all fallback fonts as a comma-separated
     * string ready to embed in a {@code font-family} declaration, e.g.
     * {@code 'Noto Sans', 'Noto Sans Symbols', ...}.
     */
    public String getFontFamilyNames() {
        return fontFamilyNamesCache;
    }

    public void registerFallbackFonts(PdfRendererBuilder builder) {
        for (BaseFontMapping.FontInfo font : fontsAdded) {
            builder.useFont(
                () -> FallbackFontMapping.class.getResourceAsStream(font.resourcePath),
                font.cssName, font.weight, font.style, true);
        }
    }

    private static String toCSSFontFamilyList(List<BaseFontMapping.FontInfo> fontsList) {
        return fontsList.stream()
            .map(fi -> '\'' + fi.cssNameSingleQuoteEscaped() + '\'')
            .distinct()
            .collect(Collectors.joining(", "));
    }
}