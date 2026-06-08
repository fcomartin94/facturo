package com.facturo.mapper;

import com.facturo.dto.response.FacturaResponse;
import com.facturo.entity.Factura;
import com.facturo.entity.LineaFactura;
import org.mapstruct.Mapper;
import org.mapstruct.Mapping;

import java.util.List;

/**
 * MapStruct mapper that converts {@link Factura} and {@link LineaFactura} JPA entities
 * to their corresponding response DTOs. The implementation is generated at compile
 * time and registered as a Spring bean ({@code componentModel = "spring"}).
 */
@Mapper(componentModel = "spring")
public interface FacturaMapper {

    /**
     * Maps a Factura entity to its response DTO.
     * The {@code cliente} field is populated manually via a Java expression because
     * MapStruct cannot infer the nested {@code ClienteResumen} record from the
     * {@code Factura.cliente} association without an explicit mapping rule.
     */
    @Mapping(target = "cliente", expression = "java(new com.facturo.dto.response.FacturaResponse.ClienteResumen(factura.getCliente().getId(), factura.getCliente().getNombre(), factura.getCliente().getNif()))")
    FacturaResponse toResponse(Factura factura);

    /** Maps a list of Factura entities to a list of response DTOs. */
    List<FacturaResponse> toResponseList(List<Factura> facturas);

    /** Maps a single LineaFactura entity to its nested response DTO. */
    FacturaResponse.LineaFacturaResponse toLineaResponse(LineaFactura linea);
}
