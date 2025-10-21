package com.hallowelt.mediawiki.services.html2pdf;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.boot.Banner;

@SpringBootApplication
public class Html2PdfApplication {

	public static void main(String[] args) {
		SpringApplication app = new SpringApplication(Html2PdfApplication.class);
		app.setBannerMode(Banner.Mode.OFF);
		app.run(args);
	}

}
