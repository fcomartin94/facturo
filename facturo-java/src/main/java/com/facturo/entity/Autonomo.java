package com.facturo.entity;

import jakarta.persistence.*;
import lombok.*;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.security.core.userdetails.UserDetails;

import java.time.LocalDateTime;
import java.util.Collection;
import java.util.List;

@Entity
@Table(name = "autonomos")
@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Autonomo implements UserDetails {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(nullable = false, unique = true)
    private String email;

    @Column(nullable = false)
    private String password;

    @Column(nullable = false)
    private String nombre;

    @Column(nullable = false)
    private String apellidos;

    @Column(nullable = false, unique = true, length = 9)
    private String nif;

    private String direccion;
    private String codigoPostal;
    private String ciudad;
    private String provincia;
    private String telefono;

    @Column(updatable = false)
    private LocalDateTime creadoEn;

    @OneToMany(mappedBy = "autonomo", cascade = CascadeType.ALL, fetch = FetchType.LAZY)
    private List<Cliente> clientes;

    @OneToMany(mappedBy = "autonomo", cascade = CascadeType.ALL, fetch = FetchType.LAZY)
    private List<Factura> facturas;

    @PrePersist
    protected void onCreate() {
        creadoEn = LocalDateTime.now();
    }

    // --- UserDetails ---
    @Override
    public Collection<? extends GrantedAuthority> getAuthorities() {
        return List.of();
    }

    @Override
    public String getUsername() {
        return email;
    }

    @Override
    public boolean isAccountNonExpired() { return true; }

    @Override
    public boolean isAccountNonLocked() { return true; }

    @Override
    public boolean isCredentialsNonExpired() { return true; }

    @Override
    public boolean isEnabled() { return true; }
}
