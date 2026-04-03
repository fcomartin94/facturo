package com.facturo.controller;

import com.facturo.dto.request.FacturaRequest;
import com.facturo.dto.response.FacturaResponse;
import com.facturo.entity.Autonomo;
import com.facturo.entity.Factura.EstadoFactura;
import com.facturo.service.FacturaService;
import com.facturo.service.PdfService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpStatus;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/facturas")
@RequiredArgsConstructor
public class FacturaController {

    private final FacturaService facturaService;
    private final PdfService pdfService;

    @GetMapping
    public ResponseEntity<List<FacturaResponse>> listar(@AuthenticationPrincipal Autonomo autonomo) {
        return ResponseEntity.ok(facturaService.listarFacturas(autonomo));
    }

    @GetMapping("/{id}")
    public ResponseEntity<FacturaResponse> obtener(@PathVariable Long id,
                                                   @AuthenticationPrincipal Autonomo autonomo) {
        return ResponseEntity.ok(facturaService.obtenerFactura(id, autonomo));
    }

    @PostMapping
    public ResponseEntity<FacturaResponse> crear(@Valid @RequestBody FacturaRequest request,
                                                 @AuthenticationPrincipal Autonomo autonomo) {
        return ResponseEntity.status(HttpStatus.CREATED).body(facturaService.crearFactura(request, autonomo));
    }

    @PatchMapping("/{id}/estado")
    public ResponseEntity<FacturaResponse> cambiarEstado(@PathVariable Long id,
                                                         @RequestParam EstadoFactura estado,
                                                         @AuthenticationPrincipal Autonomo autonomo) {
        return ResponseEntity.ok(facturaService.cambiarEstado(id, estado, autonomo));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> eliminar(@PathVariable Long id,
                                          @AuthenticationPrincipal Autonomo autonomo) {
        facturaService.eliminarFactura(id, autonomo);
        return ResponseEntity.noContent().build();
    }

    // El endpoint "efecto WOW" — genera y descarga el PDF de la factura
    @GetMapping("/{id}/pdf")
    public ResponseEntity<byte[]> descargarPdf(@PathVariable Long id,
                                               @AuthenticationPrincipal Autonomo autonomo) {
        byte[] pdf = pdfService.generarPdf(id, autonomo);
        FacturaResponse factura = facturaService.obtenerFactura(id, autonomo);

        return ResponseEntity.ok()
                .header(HttpHeaders.CONTENT_DISPOSITION,
                        "attachment; filename=\"factura-" + factura.numeroFactura() + ".pdf\"")
                .contentType(MediaType.APPLICATION_PDF)
                .body(pdf);
    }
}
