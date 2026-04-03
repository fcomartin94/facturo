package com.facturo.service;

import com.facturo.entity.Autonomo;
import com.facturo.entity.Factura;
import com.facturo.entity.LineaFactura;
import com.facturo.exception.ResourceNotFoundException;
import com.facturo.repository.FacturaRepository;
import com.itextpdf.kernel.colors.ColorConstants;
import com.itextpdf.kernel.colors.DeviceRgb;
import com.itextpdf.kernel.font.PdfFont;
import com.itextpdf.kernel.font.PdfFontFactory;
import com.itextpdf.kernel.geom.PageSize;
import com.itextpdf.kernel.pdf.PdfDocument;
import com.itextpdf.kernel.pdf.PdfWriter;
import com.itextpdf.layout.Document;
import com.itextpdf.layout.borders.Border;
import com.itextpdf.layout.borders.SolidBorder;
import com.itextpdf.layout.element.*;
import com.itextpdf.layout.properties.TextAlignment;
import com.itextpdf.layout.properties.UnitValue;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.math.BigDecimal;
import java.time.format.DateTimeFormatter;

@Service
@RequiredArgsConstructor
public class PdfService {

    private static final DateTimeFormatter DATE_FORMAT = DateTimeFormatter.ofPattern("dd/MM/yyyy");
    private static final DeviceRgb COLOR_PRIMARY = new DeviceRgb(30, 64, 175);   // azul corporativo
    private static final DeviceRgb COLOR_LIGHT = new DeviceRgb(239, 246, 255);   // fondo suave
    private static final DeviceRgb COLOR_GRAY = new DeviceRgb(107, 114, 128);

    private final FacturaRepository facturaRepository;

    public byte[] generarPdf(Long facturaId, Autonomo autonomo) {
        Factura factura = facturaRepository.findByIdAndAutonomoId(facturaId, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Factura no encontrada con id: " + facturaId));

        try (ByteArrayOutputStream baos = new ByteArrayOutputStream()) {
            PdfWriter writer = new PdfWriter(baos);
            PdfDocument pdfDoc = new PdfDocument(writer);
            Document doc = new Document(pdfDoc, PageSize.A4);
            doc.setMargins(40, 50, 40, 50);

            PdfFont fontBold = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA_BOLD);
            PdfFont fontNormal = PdfFontFactory.createFont(com.itextpdf.io.font.constants.StandardFonts.HELVETICA);

            // --- CABECERA ---
            Table header = new Table(UnitValue.createPercentArray(new float[]{60, 40})).useAllAvailableWidth();

            // Datos del autónomo (izquierda)
            Cell autonomoCell = new Cell().setBorder(Border.NO_BORDER).setPadding(0);
            autonomoCell.add(new Paragraph(autonomo.getNombre() + " " + autonomo.getApellidos())
                    .setFont(fontBold).setFontSize(16).setFontColor(COLOR_PRIMARY));
            autonomoCell.add(new Paragraph("NIF: " + autonomo.getNif()).setFont(fontNormal).setFontSize(9).setFontColor(COLOR_GRAY));
            if (autonomo.getDireccion() != null)
                autonomoCell.add(new Paragraph(autonomo.getDireccion()).setFont(fontNormal).setFontSize(9));
            if (autonomo.getCiudad() != null)
                autonomoCell.add(new Paragraph(autonomo.getCodigoPostal() + " " + autonomo.getCiudad()).setFont(fontNormal).setFontSize(9));
            if (autonomo.getTelefono() != null)
                autonomoCell.add(new Paragraph("Tel: " + autonomo.getTelefono()).setFont(fontNormal).setFontSize(9));
            autonomoCell.add(new Paragraph(autonomo.getEmail()).setFont(fontNormal).setFontSize(9));
            header.addCell(autonomoCell);

            // Datos de la factura (derecha)
            Cell facturaInfoCell = new Cell().setBorder(Border.NO_BORDER)
                    .setBackgroundColor(COLOR_LIGHT).setPadding(12).setBorderRadius(new com.itextpdf.layout.properties.BorderRadius(4));

            facturaInfoCell.add(new Paragraph("FACTURA")
                    .setFont(fontBold).setFontSize(20).setFontColor(COLOR_PRIMARY).setTextAlignment(TextAlignment.RIGHT));
            facturaInfoCell.add(new Paragraph("Nº " + factura.getNumeroFactura())
                    .setFont(fontBold).setFontSize(12).setTextAlignment(TextAlignment.RIGHT));
            facturaInfoCell.add(new Paragraph("Fecha: " + factura.getFechaEmision().format(DATE_FORMAT))
                    .setFont(fontNormal).setFontSize(9).setTextAlignment(TextAlignment.RIGHT));
            if (factura.getFechaVencimiento() != null) {
                facturaInfoCell.add(new Paragraph("Vencimiento: " + factura.getFechaVencimiento().format(DATE_FORMAT))
                        .setFont(fontNormal).setFontSize(9).setTextAlignment(TextAlignment.RIGHT));
            }
            facturaInfoCell.add(new Paragraph("Estado: " + factura.getEstado().name())
                    .setFont(fontNormal).setFontSize(9).setFontColor(COLOR_GRAY).setTextAlignment(TextAlignment.RIGHT));
            header.addCell(facturaInfoCell);

            doc.add(header);
            doc.add(new Paragraph("\n"));

            // --- DATOS DEL CLIENTE ---
            Cell clienteBox = new Cell()
                    .setBorderLeft(new SolidBorder(COLOR_PRIMARY, 3))
                    .setBorderRight(Border.NO_BORDER).setBorderTop(Border.NO_BORDER).setBorderBottom(Border.NO_BORDER)
                    .setPaddingLeft(10).setPaddingTop(5).setPaddingBottom(5);

            clienteBox.add(new Paragraph("FACTURAR A:").setFont(fontBold).setFontSize(8).setFontColor(COLOR_GRAY));
            clienteBox.add(new Paragraph(factura.getCliente().getNombre()).setFont(fontBold).setFontSize(11));
            clienteBox.add(new Paragraph("NIF: " + factura.getCliente().getNif()).setFont(fontNormal).setFontSize(9));
            if (factura.getCliente().getDireccion() != null)
                clienteBox.add(new Paragraph(factura.getCliente().getDireccion()).setFont(fontNormal).setFontSize(9));
            if (factura.getCliente().getEmail() != null)
                clienteBox.add(new Paragraph(factura.getCliente().getEmail()).setFont(fontNormal).setFontSize(9));

            Table clienteTable = new Table(1).useAllAvailableWidth();
            clienteTable.addCell(clienteBox);
            doc.add(clienteTable);
            doc.add(new Paragraph("\n"));

            // --- LÍNEAS DE FACTURA ---
            Table lineasTable = new Table(UnitValue.createPercentArray(new float[]{45, 15, 20, 20})).useAllAvailableWidth();

            // Cabeceras
            for (String cabecera : new String[]{"CONCEPTO", "CANTIDAD", "PRECIO UNIT.", "IMPORTE"}) {
                lineasTable.addHeaderCell(new Cell()
                        .setBackgroundColor(COLOR_PRIMARY)
                        .setBorder(Border.NO_BORDER)
                        .setPadding(8)
                        .add(new Paragraph(cabecera)
                                .setFont(fontBold).setFontSize(9).setFontColor(ColorConstants.WHITE)
                                .setTextAlignment(cabecera.equals("CONCEPTO") ? TextAlignment.LEFT : TextAlignment.RIGHT)));
            }

            // Filas
            boolean alternar = false;
            for (LineaFactura linea : factura.getLineas()) {
                DeviceRgb rowBg = alternar ? COLOR_LIGHT : new DeviceRgb(255, 255, 255);
                addLinea(lineasTable, linea, fontNormal, rowBg);
                alternar = !alternar;
            }

            doc.add(lineasTable);
            doc.add(new Paragraph("\n"));

            // --- TOTALES ---
            Table totalesTable = new Table(UnitValue.createPercentArray(new float[]{60, 40})).useAllAvailableWidth();

            Cell espacio = new Cell().setBorder(Border.NO_BORDER);
            totalesTable.addCell(espacio);

            Cell totalesCell = new Cell().setBorder(Border.NO_BORDER).setPadding(0);

            addFilaTotales(totalesCell, "Base Imponible", formatEuros(factura.getBaseImponible()), fontNormal, fontBold, false);
            addFilaTotales(totalesCell, "IVA (" + factura.getPorcentajeIva() + "%)", formatEuros(factura.getCuotaIva()), fontNormal, fontBold, false);
            addFilaTotales(totalesCell, "IRPF (-" + factura.getPorcentajeIrpf() + "%)", "-" + formatEuros(factura.getCuotaIrpf()), fontNormal, fontBold, false);

            // Total final
            Table totalFinalTable = new Table(UnitValue.createPercentArray(new float[]{50, 50})).useAllAvailableWidth();
            totalFinalTable.addCell(new Cell().setBackgroundColor(COLOR_PRIMARY).setBorder(Border.NO_BORDER).setPadding(10)
                    .add(new Paragraph("TOTAL A PAGAR").setFont(fontBold).setFontSize(10).setFontColor(ColorConstants.WHITE)));
            totalFinalTable.addCell(new Cell().setBackgroundColor(COLOR_PRIMARY).setBorder(Border.NO_BORDER).setPadding(10)
                    .add(new Paragraph(formatEuros(factura.getTotal())).setFont(fontBold).setFontSize(12)
                            .setFontColor(ColorConstants.WHITE).setTextAlignment(TextAlignment.RIGHT)));
            totalesCell.add(totalFinalTable);

            totalesTable.addCell(totalesCell);
            doc.add(totalesTable);

            // --- NOTAS ---
            if (factura.getNotas() != null && !factura.getNotas().isBlank()) {
                doc.add(new Paragraph("\n"));
                doc.add(new Paragraph("Notas:").setFont(fontBold).setFontSize(9).setFontColor(COLOR_GRAY));
                doc.add(new Paragraph(factura.getNotas()).setFont(fontNormal).setFontSize(9));
            }

            doc.close();
            return baos.toByteArray();

        } catch (IOException e) {
            throw new RuntimeException("Error generando el PDF de la factura", e);
        }
    }

    private void addLinea(Table table, LineaFactura linea, PdfFont font, DeviceRgb bg) {
        table.addCell(cellLinea(linea.getConcepto(), font, bg, TextAlignment.LEFT));
        table.addCell(cellLinea(linea.getCantidad().toPlainString(), font, bg, TextAlignment.RIGHT));
        table.addCell(cellLinea(formatEuros(linea.getPrecioUnitario()), font, bg, TextAlignment.RIGHT));
        table.addCell(cellLinea(formatEuros(linea.getImporte()), font, bg, TextAlignment.RIGHT));
    }

    private Cell cellLinea(String text, PdfFont font, DeviceRgb bg, TextAlignment align) {
        return new Cell().setBackgroundColor(bg).setBorder(Border.NO_BORDER).setPadding(8)
                .add(new Paragraph(text).setFont(font).setFontSize(9).setTextAlignment(align));
    }

    private void addFilaTotales(Cell container, String label, String value, PdfFont fontNormal, PdfFont fontBold, boolean highlight) {
        Table row = new Table(UnitValue.createPercentArray(new float[]{55, 45})).useAllAvailableWidth();
        row.addCell(new Cell().setBorder(Border.NO_BORDER).setPaddingTop(4).setPaddingBottom(4)
                .add(new Paragraph(label).setFont(fontNormal).setFontSize(9).setFontColor(COLOR_GRAY)));
        row.addCell(new Cell().setBorder(Border.NO_BORDER).setPaddingTop(4).setPaddingBottom(4)
                .add(new Paragraph(value).setFont(fontBold).setFontSize(9).setTextAlignment(TextAlignment.RIGHT)));
        container.add(row);
    }

    private String formatEuros(BigDecimal amount) {
        if (amount == null) return "0,00 €";
        return String.format("%,.2f €", amount).replace(",", "X").replace(".", ",").replace("X", ".");
    }
}
