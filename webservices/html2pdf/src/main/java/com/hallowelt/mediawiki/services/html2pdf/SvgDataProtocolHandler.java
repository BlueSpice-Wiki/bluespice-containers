package com.hallowelt.mediawiki.services.html2pdf;

import org.apache.batik.util.ParsedURL;
import org.apache.batik.util.ParsedURLData;
import org.apache.batik.util.ParsedURLDataProtocolHandler;

/**
 * Preserves local fragment references inside SVGs embedded through data URLs.
 *
 * <p>Batik's default data protocol handler ignores the base URL when resolving
 * relative references. Consequently, a reference such as
 * {@code <use xlink:href="#box"/>} loses its enclosing data document and may be
 * treated as an external resource instead of resolving to a local definition.
 * For fragment-only references, this handler retains the original data URL and
 * replaces only its fragment, preserving both percent-encoded and base64 data.
 *
 * <p>All other references use Batik's default handling. Registering this handler
 * does not relax the renderer's script or external-resource security policy.
 */
final class SvgDataProtocolHandler extends ParsedURLDataProtocolHandler {

	@Override
	public ParsedURLData parseURL(ParsedURL baseURL, String url) {
		if (url.startsWith("#")) {
			ParsedURLData resolved = super.parseURL(baseURL.toString());
			resolved.ref = url.substring(1);
			return resolved;
		}
		return super.parseURL(baseURL, url);
	}
}