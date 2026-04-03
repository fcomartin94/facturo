package com.facturo.mapper;

import com.facturo.dto.response.FacturaResponse;
import com.facturo.entity.Factura;
import com.facturo.entity.LineaFactura;
import org.mapstruct.Mapper;
import org.mapstruct.Mapping;

import java.util.List;

@Mapper(componentModel = "spring")
public interface FacturaMapper {

    @Mapping(target = "cliente", expression = "java(new com.facturo.dto.response.FacturaResponse.ClienteResumen(factura.getCliente().getId(), factura.getCliente().getNombre(), factura.getCliente().getNif()))")
    FacturaResponse toResponse(Factura factura);

    List<FacturaResponse> toResponseList(List<Factura> facturas);

    FacturaResponse.LineaFacturaResponse toLineaResponse(LineaFactura linea);
}
