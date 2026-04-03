package com.facturo.service;

import com.facturo.dto.request.LoginRequest;
import com.facturo.dto.request.RegisterRequest;
import com.facturo.dto.response.AuthResponse;
import com.facturo.entity.Autonomo;
import com.facturo.exception.BusinessException;
import com.facturo.repository.AutonomoRepository;
import com.facturo.security.JwtUtil;
import lombok.RequiredArgsConstructor;
import org.springframework.security.authentication.AuthenticationManager;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

@Service
@RequiredArgsConstructor
public class AuthService {

    private final AutonomoRepository autonomoRepository;
    private final PasswordEncoder passwordEncoder;
    private final JwtUtil jwtUtil;
    private final AuthenticationManager authenticationManager;

    @Transactional
    public AuthResponse register(RegisterRequest request) {
        if (autonomoRepository.existsByEmail(request.email())) {
            throw new BusinessException("Ya existe un usuario con el email: " + request.email());
        }
        if (autonomoRepository.existsByNif(request.nif())) {
            throw new BusinessException("Ya existe un usuario con el NIF: " + request.nif());
        }

        Autonomo autonomo = Autonomo.builder()
                .email(request.email())
                .password(passwordEncoder.encode(request.password()))
                .nombre(request.nombre())
                .apellidos(request.apellidos())
                .nif(request.nif())
                .direccion(request.direccion())
                .codigoPostal(request.codigoPostal())
                .ciudad(request.ciudad())
                .provincia(request.provincia())
                .telefono(request.telefono())
                .build();

        autonomoRepository.save(autonomo);
        String token = jwtUtil.generateToken(autonomo);

        return new AuthResponse(token, autonomo.getEmail(), autonomo.getNombre(), autonomo.getApellidos());
    }

    public AuthResponse login(LoginRequest request) {
        authenticationManager.authenticate(
                new UsernamePasswordAuthenticationToken(request.email(), request.password())
        );
        Autonomo autonomo = autonomoRepository.findByEmail(request.email())
                .orElseThrow(() -> new BusinessException("Usuario no encontrado"));

        String token = jwtUtil.generateToken(autonomo);
        return new AuthResponse(token, autonomo.getEmail(), autonomo.getNombre(), autonomo.getApellidos());
    }
}
