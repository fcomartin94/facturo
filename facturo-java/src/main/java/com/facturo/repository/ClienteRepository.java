package com.facturo.repository;

import com.facturo.entity.Cliente;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.List;
import java.util.Optional;

public interface ClienteRepository extends JpaRepository<Cliente, Long> {

    // Multitenencia: siempre filtramos por autonomo_id
    List<Cliente> findAllByAutonomoId(Long autonomoId);

    Optional<Cliente> findByIdAndAutonomoId(Long id, Long autonomoId);

    boolean existsByNifAndAutonomoId(String nif, Long autonomoId);
}
