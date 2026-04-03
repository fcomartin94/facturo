package com.facturo.service;

import com.facturo.dto.request.FacturaRequest;
import com.facturo.dto.response.FacturaResponse;
import com.facturo.entity.Autonomo;
import com.facturo.entity.Cliente;
import com.facturo.entity.Factura;
import com.facturo.entity.LineaFactura;
import com.facturo.exception.BusinessException;
import com.facturo.exception.ResourceNotFoundException;
import com.facturo.mapper.FacturaMapper;
import com.facturo.repository.ClienteRepository;
import com.facturo.repository.FacturaRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.util.List;

@Service
@RequiredArgsConstructor
public class FacturaService {

    private final FacturaRepository facturaRepository;
    private final ClienteRepository clienteRepository;
    private final FacturaMapper facturaMapper;

    public List<FacturaResponse> listarFacturas(Autonomo autonomo) {
        return facturaMapper.toResponseList(
                facturaRepository.findAllByAutonomoIdOrderByFechaEmisionDesc(autonomo.getId())
        );
    }

    public FacturaResponse obtenerFactura(Long id, Autonomo autonomo) {
        Factura factura = facturaRepository.findByIdAndAutonomoId(id, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Factura no encontrada con id: " + id));
        return facturaMapper.toResponse(factura);
    }

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

        // Añadir líneas
        List<LineaFactura> lineas = request.lineas().stream()
                .map(lineaReq -> {
                    LineaFactura linea = LineaFactura.builder()
                            .factura(factura)
                            .concepto(lineaReq.concepto())
                            .cantidad(lineaReq.cantidad())
                            .precioUnitario(lineaReq.precioUnitario())
                            .importe(lineaReq.cantidad().multiply(lineaReq.precioUnitario()))
                            .build();
                    return linea;
                })
                .toList();

        factura.getLineas().addAll(lineas);
        factura.calcularTotales();

        return facturaMapper.toResponse(facturaRepository.save(factura));
    }

    @Transactional
    public FacturaResponse cambiarEstado(Long id, Factura.EstadoFactura nuevoEstado, Autonomo autonomo) {
        Factura factura = facturaRepository.findByIdAndAutonomoId(id, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Factura no encontrada con id: " + id));

        validarTransicionEstado(factura.getEstado(), nuevoEstado);
        factura.setEstado(nuevoEstado);

        return facturaMapper.toResponse(facturaRepository.save(factura));
    }

    @Transactional
    public void eliminarFactura(Long id, Autonomo autonomo) {
        Factura factura = facturaRepository.findByIdAndAutonomoId(id, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Factura no encontrada con id: " + id));

        if (factura.getEstado() == Factura.EstadoFactura.EMITIDA || factura.getEstado() == Factura.EstadoFactura.PAGADA) {
            throw new BusinessException("No se puede eliminar una factura emitida o pagada. Usa el estado CANCELADA.");
        }

        facturaRepository.delete(factura);
    }

    // Genera el número de factura correlativo por año: 2024-001, 2024-002...
    private String generarNumeroFactura(Long autonomoId, int year) {
        int correlativo = facturaRepository.findMaxCorrelativoByAnio(autonomoId, String.valueOf(year)) + 1;
        return String.format("%d-%03d", year, correlativo);
    }

    private void validarTransicionEstado(Factura.EstadoFactura actual, Factura.EstadoFactura nuevo) {
        boolean invalido = switch (actual) {
            case BORRADOR -> nuevo != Factura.EstadoFactura.EMITIDA && nuevo != Factura.EstadoFactura.CANCELADA;
            case EMITIDA -> nuevo != Factura.EstadoFactura.PAGADA && nuevo != Factura.EstadoFactura.VENCIDA && nuevo != Factura.EstadoFactura.CANCELADA;
            case PAGADA, VENCIDA, CANCELADA -> true; // estados finales
        };

        if (invalido) {
            throw new BusinessException(
                    String.format("No se puede cambiar el estado de %s a %s", actual, nuevo)
            );
        }
    }
}
