package com.facturo.repository;

import com.facturo.entity.Factura;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;

import java.util.List;
import java.util.Optional;

public interface FacturaRepository extends JpaRepository<Factura, Long> {

    List<Factura> findAllByAutonomoIdOrderByFechaEmisionDesc(Long autonomoId);

    Optional<Factura> findByIdAndAutonomoId(Long id, Long autonomoId);

    boolean existsByNumeroFacturaAndAutonomoId(String numeroFactura, Long autonomoId);

    @Query("SELECT COALESCE(MAX(CAST(SUBSTRING(f.numeroFactura, 6) AS int)), 0) FROM Factura f WHERE f.autonomo.id = :autonomoId AND f.numeroFactura LIKE CONCAT(:year, '-%')")
    int findMaxCorrelativoByAnio(Long autonomoId, String year);
}
