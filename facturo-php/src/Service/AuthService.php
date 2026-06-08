<?php
declare(strict_types=1);

namespace Facturo\Service;

use Facturo\Model\Autonomo;
use Facturo\Repository\AutonomoRepository;
use Facturo\Security\JwtUtil;
use Facturo\Validation\Validator;
use Facturo\Exception\BusinessException;
use Facturo\Exception\ValidationException;

class AuthService
{
    private AutonomoRepository $autonomoRepo;

    public function __construct()
    {
        $this->autonomoRepo = new AutonomoRepository();
    }

    /**
     * Registra un nuevo autónomo.
     * Retorna el autónomo creado (sin password) + el JWT inicial.
     *
     * @param array<string, mixed> $data Payload del JSON con email, password, nombre, apellidos, nif.
     * @return array{autonomo: array<string, mixed>, token: string}
     * @throws ValidationException Si faltan campos obligatorios o el email no tiene formato válido.
     * @throws BusinessException   Si el email ya está registrado (HTTP 409).
     */
    public function register(array $data): array
    {
        // Validación
        (new Validator())
            ->required($data['email']    ?? null, 'email')
            ->email($data['email']       ?? '',   'email')
            ->required($data['password'] ?? null, 'password')
            ->minLength($data['password'] ?? '', 'password', 8)
            ->required($data['nombre']   ?? null, 'nombre')
            ->required($data['apellidos']?? null, 'apellidos')
            ->required($data['nif']      ?? null, 'nif')
            ->throwIfInvalid();

        // Email único
        if ($this->autonomoRepo->existsByEmail($data['email'])) {
            throw new BusinessException('El email ya está registrado', 409);
        }

        // Hashear contraseña — BCrypt con cost=12 (seguro y razonablemente rápido)
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $autonomo = new Autonomo(
            id:        null,
            email:     $data['email'],
            password:  $hashedPassword,
            nombre:    $data['nombre'],
            apellidos: $data['apellidos'],
            nif:       $data['nif'],
            direccion: $data['direccion']    ?? null,
            codigoPostal: $data['codigoPostal'] ?? null,
            ciudad:    $data['ciudad']       ?? null,
            provincia: $data['provincia']    ?? null,
            telefono:  $data['telefono']     ?? null,
        );

        $created = $this->autonomoRepo->create($autonomo);

        return [
            'autonomo' => $created->toArray(),
            'token'    => JwtUtil::generate($created->id),
        ];
    }

    /**
     * Login: verifica credenciales y devuelve JWT si son correctas.
     *
     * @param array<string, mixed> $data Payload con 'email' y 'password'.
     * @return array{autonomo: array<string, mixed>, token: string}
     * @throws ValidationException Si faltan campos obligatorios.
     * @throws BusinessException   Si las credenciales son incorrectas (HTTP 401).
     */
    public function login(array $data): array
    {
        (new Validator())
            ->required($data['email']    ?? null, 'email')
            ->required($data['password'] ?? null, 'password')
            ->throwIfInvalid();

        $autonomo = $this->autonomoRepo->findByEmail($data['email']);

        // Mensaje genérico: no revelar si el email existe o no
        if ($autonomo === null || !password_verify($data['password'], $autonomo->password)) {
            throw new BusinessException('Credenciales incorrectas', 401);
        }

        return [
            'autonomo' => $autonomo->toArray(),
            'token'    => JwtUtil::generate($autonomo->id),
        ];
    }
}