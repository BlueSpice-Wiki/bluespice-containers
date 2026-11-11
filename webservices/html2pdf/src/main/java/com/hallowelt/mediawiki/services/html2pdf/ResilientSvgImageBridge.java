package com.hallowelt.mediawiki.services.html2pdf;

import java.awt.geom.Rectangle2D;

import org.apache.batik.bridge.BridgeContext;
import org.apache.batik.bridge.BridgeException;
import org.apache.batik.bridge.SVGImageElementBridge;
import org.apache.batik.gvt.GraphicsNode;
import org.apache.batik.gvt.ShapeNode;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.w3c.dom.Element;

/**
 * Keeps an SVG renderable when its security policy blocks a referenced sub-image.
 *
 * <p>Batik normally aborts the drawing when an {@code <image>} references a
 * disallowed resource, such as a relative file left over from an editor export.
 * This bridge logs security refusals and substitutes an empty graphics node,
 * leaving that sub-image blank while preserving the rest of the drawing. It
 * neither enables external access nor modifies the source SVG, and unrelated
 * rendering errors are propagated unchanged.
 *
 * <p>Installed through the build-time patch to OpenHTMLtoPDF's SVG image bridge,
 * including the bridge contexts used by nested SVG images.
 */
public class ResilientSvgImageBridge extends SVGImageElementBridge {

	private static final Logger logger = LoggerFactory.getLogger(ResilientSvgImageBridge.class);

	@Override
	protected GraphicsNode buildImageGraphicsNode(BridgeContext context, Element element) {
		try {
			return super.buildImageGraphicsNode(context, element);
		} catch (BridgeException exception) {
			if (!ERR_URI_UNSECURE.equals(exception.getCode())) {
				throw exception;
			}
			logger.warn("Skipping blocked SVG sub-image: {}", exception.getMessage());
			ShapeNode emptyImage = new ShapeNode();
			emptyImage.setShape(new Rectangle2D.Float());
			return emptyImage;
		}
	}
}