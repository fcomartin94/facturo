package com.facturo.entity;

import jakarta.persistence.*;
import lombok.*;

import java.time.LocalDateTime;
import java.util.List;

@Entity
@Table(name = "clientes")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Cliente {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    // Multitenencia: cada cliente pertenece a un autónomo
    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "autonomo_id", nullable = false)
    private Autonomo autonomo;

    @Column(nullable = false)
    private String nombre;

    @Column(nullable = false, length = 15)
    private String nif;

    private String email;
    private String telefono;
    private String direccion;
    private String codigoPostal;
    private String ciudad;
    private String provincia;
    private String pais;

    @Column(updatable = false)
    private LocalDateTime creadoEn;

    @OneToMany(mappedBy = "cliente", cascade = CascadeType.ALL, fetch = FetchType.LAZY)
    private List<Factura> facturas;

    @PrePersist
    protected void onCreate() {
        creadoEn = LocalDateTime.now();
        if (pais == null) pais = "España";
    }
}
