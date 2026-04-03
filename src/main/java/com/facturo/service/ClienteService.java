package com.facturo.service;

import com.facturo.dto.request.ClienteRequest;
import com.facturo.dto.response.ClienteResponse;
import com.facturo.entity.Autonomo;
import com.facturo.entity.Cliente;
import com.facturo.exception.BusinessException;
import com.facturo.exception.ResourceNotFoundException;
import com.facturo.mapper.ClienteMapper;
import com.facturo.repository.ClienteRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
@RequiredArgsConstructor
public class ClienteService {

    private final ClienteRepository clienteRepository;
    private final ClienteMapper clienteMapper;

    public List<ClienteResponse> listarClientes(Autonomo autonomo) {
        return clienteMapper.toResponseList(
                clienteRepository.findAllByAutonomoId(autonomo.getId())
        );
    }

    public ClienteResponse obtenerCliente(Long id, Autonomo autonomo) {
        Cliente cliente = clienteRepository.findByIdAndAutonomoId(id, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Cliente no encontrado con id: " + id));
        return clienteMapper.toResponse(cliente);
    }

    @Transactional
    public ClienteResponse crearCliente(ClienteRequest request, Autonomo autonomo) {
        if (clienteRepository.existsByNifAndAutonomoId(request.nif(), autonomo.getId())) {
            throw new BusinessException("Ya tienes un cliente con el NIF: " + request.nif());
        }

        Cliente cliente = Cliente.builder()
                .autonomo(autonomo)
                .nombre(request.nombre())
                .nif(request.nif())
                .email(request.email())
                .telefono(request.telefono())
                .direccion(request.direccion())
                .codigoPostal(request.codigoPostal())
                .ciudad(request.ciudad())
                .provincia(request.provincia())
                .pais(request.pais())
                .build();

        return clienteMapper.toResponse(clienteRepository.save(cliente));
    }

    @Transactional
    public ClienteResponse actualizarCliente(Long id, ClienteRequest request, Autonomo autonomo) {
        Cliente cliente = clienteRepository.findByIdAndAutonomoId(id, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Cliente no encontrado con id: " + id));

        cliente.setNombre(request.nombre());
        cliente.setNif(request.nif());
        cliente.setEmail(request.email());
        cliente.setTelefono(request.telefono());
        cliente.setDireccion(request.direccion());
        cliente.setCodigoPostal(request.codigoPostal());
        cliente.setCiudad(request.ciudad());
        cliente.setProvincia(request.provincia());
        cliente.setPais(request.pais());

        return clienteMapper.toResponse(clienteRepository.save(cliente));
    }

    @Transactional
    public void eliminarCliente(Long id, Autonomo autonomo) {
        Cliente cliente = clienteRepository.findByIdAndAutonomoId(id, autonomo.getId())
                .orElseThrow(() -> new ResourceNotFoundException("Cliente no encontrado con id: " + id));
        clienteRepository.delete(cliente);
    }
}
