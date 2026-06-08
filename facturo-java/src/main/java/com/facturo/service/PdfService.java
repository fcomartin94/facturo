package com.facturo.service;

import com.facturo.entity.Autonomo;
import com.facturo.entity.Factura;
import com.facturo.exception.ResourceNotFoundException;
import com.facturo.repository.FacturaRepository;
import lombok.RequiredArgsConstructor;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document.OutputSettings;
import org.jsoup.nodes.Document.OutputSettings.Syntax;
import org.springframework.stereotype.Service;
import org.thymeleaf.TemplateEngine;
import org.thymeleaf.context.Context;
import org.xhtmlrenderer.pdf.ITextRenderer;

import java.io.ByteArrayOutputStream;

/**
 * Servicio de generación de PDF para facturas.
 *
 * <p>Flujo de tres pasos:</p>
 * <ol>
 *   <li><strong>Thymeleaf</strong> renderiza la plantilla {@code factura.html} con los
 *       datos de la factura y el autónomo, produciendo un String HTML.</li>
 *   <li><strong>JSoup</strong> convierte ese HTML a XHTML bien formado, que es el
 *       formato que Flying Saucer necesita para parsear el documento.</li>
 *   <li><strong>Flying Saucer</strong> (con OpenPDF como backend) convierte el XHTML
 *       a un PDF A4 en memoria y lo devuelve como {@code byte[]}.</li>
 * </ol>
 *
 * <p>Licencias: Thymeleaf (Apache 2.0), Flying Saucer (LGPL), OpenPDF (LGPL),
 * JSoup (MIT). Todas permiten uso comercial sin coste.</p>
 */
@Service
@RequiredArgsConstructor
public class PdfService {

    private final FacturaRepository facturaRepository;
    private final TemplateEngine templateEngine;

    /**
     * Genera el PDF de una factura y lo devuelve como array de bytes.
     *
     * @param facturaId id de la factura
     * @param autonomo  autónomo autenticado (comprobación de propiedad)
     * @return PDF en memoria listo para enviar como respuesta HTTP
     * @throws ResourceNotFoundException si la factura no existe o es de otro autónomo
     */
    public byte[] generarPdf(Long facturaId, Autonomo autonomo) {
        Factura factura = facturaRepository.findByIdAndAutonomoId(facturaId, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Factura no encontrada: " + facturaId));

        // 1. Renderizar la plantilla HTML con Thymeleaf
        Context ctx = new Context();
        ctx.setVariable("factura", factura);
        ctx.setVariable("autonomo", autonomo);
        String html = templateEngine.process("factura", ctx);

        // 2. Convertir HTML → XHTML con JSoup (Flying Saucer requiere XHTML bien formado)
        String xhtml = Jsoup.parse(html)
                .outputSettings(new OutputSettings().syntax(Syntax.xml))
                .html();

        // 3. Renderizar XHTML → PDF con Flying Saucer + OpenPDF
        try (ByteArrayOutputStream baos = new ByteArrayOutputStream()) {
            ITextRenderer renderer = new ITextRenderer();
            renderer.setDocumentFromString(xhtml);
            renderer.layout();
            renderer.createPDF(baos);
            return baos.toByteArray();
        } catch (Exception e) {
            throw new RuntimeException("Error generando el PDF de la factura", e);
        }
    }
}
