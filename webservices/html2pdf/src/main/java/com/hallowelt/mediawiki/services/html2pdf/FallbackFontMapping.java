package com.hallowelt.mediawiki.services.html2pdf;

import java.io.InputStream;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;
import java.util.Objects;
import java.util.stream.Collectors;

import java.awt.Font;
import java.awt.FontFormatException;

import com.openhtmltopdf.extend.FSSupplier;
import com.openhtmltopdf.outputdevice.helper.BaseRendererBuilder.FontStyle;
import com.openhtmltopdf.pdfboxout.PdfRendererBuilder;

public class FallbackFontMapping {
    private List<CSSFont> fontsAdded;
    private String fontFamilyNamesCache;

    public FallbackFontMapping() {
        loadFallbackFonts();
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

        FontFileProcessor processor = new FontFileProcessor();
        for (String fileName : orderedFileNames) {
            try (InputStream is = FallbackFontMapping.class.getResourceAsStream(fileName)) {
                if (is == null) {
                    System.err.println("Font resource not found: " + fileName);
                    continue;
                }
                processor.visitFile(fileName, is);
            } catch (IOException e) {
                System.err.println("Error processing font file: " + fileName);
                e.printStackTrace();
            }
        }

        this.fontsAdded = processor.getFontsAdded();
        this.fontFamilyNamesCache = toCSSEscapedFontFamily(this.fontsAdded);
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
        for (CSSFont font : fontsAdded) {
            builder.useFont(
                 () -> FallbackFontMapping.class.getResourceAsStream(font.resourceName),
                font.family, font.weight, font.style, true);
        }
    }

    private String toCSSEscapedFontFamily(List<CSSFont> fontsList) {
        return fontsList.stream()
           .map(fnt -> '\'' + fnt.familyCssEscaped() + '\'')
           .distinct()
           .collect(Collectors.joining(", "));
    }

    private static class CSSFont {
        public final String resourceName;
        public final String family;

        /**
         * WARNING: Heuristics are used to determine if a font is bold (700) or normal (400) weight.
         */
        public final int weight;

        /**
         * WARNING: Heuristics are used to determine if a font is italic or normal style.
         */
        public final FontStyle style;

        public CSSFont(String resourceName, String family, int weight, FontStyle style) {
            this.resourceName = resourceName;
            this.family = family;
            this.weight = weight;
            this.style = style;
        }

        /**
         * WARNING: Basic escaping, may not be robust to attack.
         */
        public String familyCssEscaped() {
            return this.family.replace("'", "\\'");
        }

        @Override
        public int hashCode() {
            return Objects.hash(resourceName, family, weight, style);
        }

        @Override
        public boolean equals(Object other) {
            if (other == this) {
                return true;
            }
            
            if (other == null ||
                other.getClass() != this.getClass()) {
                return false;
            }

            CSSFont b = (CSSFont) other;

            return Objects.equals(this.resourceName, b.resourceName) &&
                   Objects.equals(this.family, b.family) &&
                   this.weight == b.weight &&
                   this.style == b.style;
        }
    }

    private static class FontFileProcessor {
        private final List<CSSFont> fontsAdded = new ArrayList<>();

        public FontFileProcessor() {}

        public List<CSSFont> getFontsAdded() {
            return this.fontsAdded;
        }

        public void visitFile(String fontFileName, InputStream fontStream) throws IOException {
            try {
                Font f = Font.createFont(Font.TRUETYPE_FONT, fontStream);
                
                String family = f.getFamily();
                // Short of parsing the font ourselves there doesn't seem to be a way
                // of getting the font properties, so we use heuristics based on font name.
                String name = f.getFontName(Locale.US).toLowerCase(Locale.US);
                int weight = name.contains("bold") ? 700 : 400;
                FontStyle style = name.contains("italic") ? FontStyle.ITALIC : FontStyle.NORMAL;

                CSSFont fnt = new CSSFont(fontFileName, family, weight, style);

                fontsAdded.add(fnt);
            } catch (FontFormatException ffe) {
                onInvalidFont(fontFileName, ffe);
            }
        }

        protected void onInvalidFont(String fontFileName, FontFormatException ffe) {
            System.err.println("Ignoring font file with invalid font format: " + fontFileName);
            System.err.println("Exception details: ");
            ffe.printStackTrace();
        }
    }
}