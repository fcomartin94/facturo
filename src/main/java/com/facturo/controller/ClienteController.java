package com.facturo.controller;

import com.facturo.dto.request.ClienteRequest;
import com.facturo.dto.response.ClienteResponse;
import com.facturo.entity.Autonomo;
import com.facturo.service.ClienteService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.annotation.AuthenticationPrincipal;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/clientes")
@RequiredArgsConstructor
public class ClienteController {

    private final ClienteService clienteService;

    @GetMapping
    public ResponseEntity<List<ClienteResponse>> listar(@AuthenticationPrincipal Autonomo autonomo) {
        return ResponseEntity.ok(clienteService.listarClientes(autonomo));
    }

    @GetMapping("/{id}")
    public ResponseEntity<ClienteResponse> obtener(@PathVariable Long id,
                                                   @AuthenticationPrincipal Autonomo autonomo) {
        return ResponseEntity.ok(clienteService.obtenerCliente(id, autonomo));
    }

    @PostMapping
    public ResponseEntity<ClienteResponse> crear(@Valid @RequestBody ClienteRequest request,
                                                 @AuthenticationPrincipal Autonomo autonomo) {
        return ResponseEntity.status(HttpStatus.CREATED).body(clienteService.crearCliente(request, autonomo));
    }

    @PutMapping("/{id}")
    public ResponseEntity<ClienteResponse> actualizar(@PathVariable Long id,
                                                      @Valid @RequestBody ClienteRequest request,
                                                      @AuthenticationPrincipal Autonomo autonomo) {
        return ResponseEntity.ok(clienteService.actualizarCliente(id, request, autonomo));
    }

    @DeleteMapping("/{id}")
    public ResponseEntity<Void> eliminar(@PathVariable Long id,
                                         @AuthenticationPrincipal Autonomo autonomo) {
        clienteService.eliminarCliente(id, autonomo);
        return ResponseEntity.noContent().build();
    }
}
