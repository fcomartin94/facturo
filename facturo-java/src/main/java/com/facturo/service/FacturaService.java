package com.facturo.service;

import com.facturo.dto.request.FacturaRequest;
import com.facturo.dto.response.FacturaResponse;
import com.facturo.entity.Autonomo;
import com.facturo.entity.Cliente;
import com.facturo.entity.Factura;
import com.facturo.entity.LineaFactura;
import com.facturo.exception.ResourceNotFoundException;
import com.facturo.mapper.FacturaMapper;
import com.facturo.repository.ClienteRepository;
import com.facturo.repository.FacturaRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.util.List;

/**
 * Lógica de negocio para la gestión de facturas.
 * Todas las operaciones están acotadas al autónomo autenticado vía autonomo_id.
 */
@Service
@RequiredArgsConstructor
public class FacturaService {

    private final FacturaRepository facturaRepository;
    private final ClienteRepository clienteRepository;
    private final FacturaMapper facturaMapper;

    /** Lista todas las facturas del autónomo, ordenadas por fecha de emisión (más reciente primero). */
    public List<FacturaResponse> listarFacturas(Autonomo autonomo) {
        return facturaMapper.toResponseList(
                facturaRepository.findAllByAutonomoIdOrderByFechaEmisionDesc(autonomo.getId())
        );
    }

    /** Obtiene una factura por ID, acotada al autónomo autenticado. */
    public FacturaResponse obtenerFactura(Long id, Autonomo autonomo) {
        Factura factura = facturaRepository.findByIdAndAutonomoId(id, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Factura no encontrada con id: " + id));
        return facturaMapper.toResponse(factura);
    }

    /** Crea una nueva factura en estado BORRADOR con totales calculados automáticamente. */
    @Transactional
    public FacturaResponse crearFactura(FacturaRequest request, Autonomo autonomo) {
        Cliente cliente = clienteRepository.findByIdAndAutonomoId(request.clienteId(), autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Cliente no encontrado con id: " + request.clienteId()));

        String numeroFactura = generarNumeroFactura(autonomo.getId(), request.fechaEmision().getYear());

        Factura factura = Factura.builder()
                .autonomo(autonomo)
                .cliente(cliente)
                .numeroFactura(numeroFactura)
                .fechaEmision(request.fechaEmision())
                .fechaVencimiento(request.fechaVencimiento())
                .porcentajeIva(request.porcentajeIva() != null ? request.porcentajeIva() : BigDecimal.valueOf(21))
                .porcentajeIrpf(request.porcentajeIrpf() != null ? request.porcentajeIrpf() : BigDecimal.valueOf(15))
                .notas(request.notas())
                .estado(Factura.EstadoFactura.BORRADOR)
                .build();

        List<LineaFactura> lineas = request.lineas().stream()
                .map(lineaReq -> LineaFactura.builder()
                            .factura(factura)
                            .concepto(lineaReq.concepto())
                            .cantidad(lineaReq.cantidad())
                            .precioUnitario(lineaReq.precioUnitario())
                            .importe(lineaReq.cantidad().multiply(lineaReq.precioUnitario()))
                            .build())
                .toList();

        factura.getLineas().addAll(lineas);
        factura.calcularTotales();

        return facturaMapper.toResponse(facturaRepository.save(factura));
    }

    /**
     * Actualiza el estado de una factura.
     * Acepta cualquier valor válido del enum sin restricciones de transición,
     * igual que la versión PHP.
     */
    @Transactional
    public FacturaResponse cambiarEstado(Long id, Factura.EstadoFactura nuevoEstado, Autonomo autonomo) {
        Factura factura = facturaRepository.findByIdAndAutonomoId(id, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Factura no encontrada con id: " + id));

        factura.setEstado(nuevoEstado);
        return facturaMapper.toResponse(facturaRepository.save(factura));
    }

    /** Elimina una factura (solo si está en estado BORRADOR). */
    @Transactional
    public void eliminarFactura(Long id, Autonomo autonomo) {
        Factura factura = facturaRepository.findByIdAndAutonomoId(id, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Factura no encontrada con id: " + id));

        facturaRepository.delete(factura);
    }

    /** Genera el siguiente número de factura secuencial para el autónomo y año dados. Formato: YYYY-NNN */
    private String generarNumeroFactura(Long autonomoId, int year) {
        int correlativo = facturaRepository.findMaxCorrelativoByAnio(autonomoId, String.valueOf(year)) + 1;
        return String.format("%d-%03d", year, correlativo);
    }
}
