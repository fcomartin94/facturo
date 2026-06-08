package com.facturo.repository;

import com.facturo.entity.Autonomo;
import org.springframework.data.jpa.repository.JpaRepository;

import java.util.Optional;

public interface AutonomoRepository extends JpaRepository<Autonomo, Long> {
    Optional<Autonomo> findByEmail(String email);
    boolean existsByEmail(String email);
    boolean existsByNif(String nif);
}
