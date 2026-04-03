package com.facturo.entity;

import jakarta.persistence.*;
import lombok.*;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;

@Entity
@Table(name = "facturas")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Factura {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    // Multitenencia
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "autonomo_id", nullable = false)
    private Autonomo autonomo;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "cliente_id", nullable = false)
    private Cliente cliente;

    @Column(nullable = false, unique = true)
    private String numeroFactura; // Ej: 2024-001

    @Column(nullable = false)
    private LocalDate fechaEmision;

    private LocalDate fechaVencimiento;

    @Column(nullable = false, precision = 5, scale = 2)
    @Builder.Default
    private BigDecimal porcentajeIva = BigDecimal.valueOf(21);

    @Column(nullable = false, precision = 5, scale = 2)
    @Builder.Default
    private BigDecimal porcentajeIrpf = BigDecimal.valueOf(15);

    // Calculados y persistidos para historial
    @Column(precision = 12, scale = 2)
    private BigDecimal baseImponible;

    @Column(precision = 12, scale = 2)
    private BigDecimal cuotaIva;

    @Column(precision = 12, scale = 2)
    private BigDecimal cuotaIrpf;

    @Column(precision = 12, scale = 2)
    private BigDecimal total;

    @Enumerated(EnumType.STRING)
    @Builder.Default
    private EstadoFactura estado = EstadoFactura.BORRADOR;

    private String notas;

    @Column(updatable = false)
    private LocalDateTime creadaEn;

    @OneToMany(mappedBy = "factura", cascade = CascadeType.ALL, orphanRemoval = true)
    @Builder.Default
    private List<LineaFactura> lineas = new ArrayList<>();

    @PrePersist
    protected void onCreate() {
        creadaEn = LocalDateTime.now();
    }

    public void calcularTotales() {
        this.baseImponible = lineas.stream()
                .map(LineaFactura::getImporte)
                .reduce(BigDecimal.ZERO, BigDecimal::add);

        this.cuotaIva = baseImponible
                .multiply(porcentajeIva)
                .divide(BigDecimal.valueOf(100));

        this.cuotaIrpf = baseImponible
                .multiply(porcentajeIrpf)
                .divide(BigDecimal.valueOf(100));

        this.total = baseImponible.add(cuotaIva).subtract(cuotaIrpf);
    }

    public enum EstadoFactura {
        BORRADOR, EMITIDA, PAGADA, VENCIDA, CANCELADA
    }
}
