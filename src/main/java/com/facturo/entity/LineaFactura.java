package com.facturo.entity;

import jakarta.persistence.*;
import lombok.*;

import java.math.BigDecimal;

@Entity
@Table(name = "lineas_factura")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class LineaFactura {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "factura_id", nullable = false)
    private Factura factura;

    @Column(nullable = false)
    private String concepto;

    @Column(nullable = false, precision = 10, scale = 2)
    private BigDecimal cantidad;

    @Column(nullable = false, precision = 10, scale = 2)
    private BigDecimal precioUnitario;

    @Column(nullable = false, precision = 12, scale = 2)
    private BigDecimal importe; // cantidad * precioUnitario

    @PrePersist
    @PreUpdate
    public void calcularImporte() {
        this.importe = this.cantidad.multiply(this.precioUnitario);
    }
}
