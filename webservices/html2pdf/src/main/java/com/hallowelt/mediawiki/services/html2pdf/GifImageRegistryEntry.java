package com.hallowelt.mediawiki.services.html2pdf;

import java.awt.AlphaComposite;
import java.awt.image.BufferedImage;
import java.io.IOException;
import java.io.InputStream;
import java.nio.charset.StandardCharsets;

import javax.imageio.ImageIO;

import org.apache.batik.ext.awt.image.GraphicsUtil;
import org.apache.batik.ext.awt.image.renderable.Filter;
import org.apache.batik.ext.awt.image.renderable.RedRable;
import org.apache.batik.ext.awt.image.spi.ImageTagRegistry;
import org.apache.batik.ext.awt.image.spi.MagicNumberRegistryEntry;
import org.apache.batik.util.ParsedURL;

/**
 * Decodes GIF image streams, including those embedded in SVG data URLs.
 *
 * <p>Batik's default GIF decoder requires a Java URL, which does not support the
 * data protocol. This entry uses Java ImageIO to decode the supplied stream
 * instead, displaying the first frame without changing resource access
 * permissions. Both GIF87a and GIF89a signatures are supported.
 */
final class GifImageRegistryEntry extends MagicNumberRegistryEntry {

	GifImageRegistryEntry() {
		super("GIF", PRIORITY + 100, new String[] { "gif" }, new String[] { "image/gif" }, new MagicNumber[] {
				new MagicNumber(0, "GIF87a".getBytes(StandardCharsets.US_ASCII)),
				new MagicNumber(0, "GIF89a".getBytes(StandardCharsets.US_ASCII))
		});
	}

	@Override
	public Filter handleStream(InputStream stream, ParsedURL url, boolean needRawData) {
		try {
			var image = ImageIO.read(stream);
			if (image != null) {
				BufferedImage raster = new BufferedImage(image.getWidth(), image.getHeight(), BufferedImage.TYPE_INT_ARGB);
				var graphics = raster.createGraphics();
				try {
					graphics.setComposite(AlphaComposite.Src);
					graphics.drawImage(image, 0, 0, null);
				} finally {
					graphics.dispose();
				}
				return new RedRable(GraphicsUtil.wrap(raster));
			}
		} catch (IOException exception) {
			org.slf4j.LoggerFactory.getLogger(GifImageRegistryEntry.class)
					.warn("Unable to decode embedded GIF image", exception);
		}
		return ImageTagRegistry.getBrokenLinkImage(this, ERR_STREAM_FORMAT_UNREADABLE, new Object[] { "GIF" });
	}
}