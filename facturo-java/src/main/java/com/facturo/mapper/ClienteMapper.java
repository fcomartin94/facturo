package com.facturo.mapper;

import com.facturo.dto.response.ClienteResponse;
import com.facturo.entity.Cliente;
import org.mapstruct.Mapper;

import java.util.List;

/**
 * MapStruct mapper that converts {@link Cliente} JPA entities to
 * {@link ClienteResponse} DTOs. The implementation is generated at compile
 * time and registered as a Spring bean ({@code componentModel = "spring"}).
 */
@Mapper(componentModel = "spring")
public interface ClienteMapper {

    /** Maps a single entity to its response DTO. */
    ClienteResponse toResponse(Cliente cliente);

    /** Maps a list of entities to a list of response DTOs. */
    List<ClienteResponse> toResponseList(List<Cliente> clientes);
}
