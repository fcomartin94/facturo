<?php
declare(strict_types=1);

namespace Facturo\Security;

use RuntimeException;
use LogicException;
use Throwable;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;

class JwtUtil
{
    private static ?Configuration $config = null;

    /**
     * Inicializa la configuración JWT.
     * Se llama UNA VEZ desde index.php tras cargar .env.
     * Separar init() de getConfig() facilita testear con clave distinta.
     *
     * @return void
     * @throws RuntimeException Si JWT_SECRET no está definido en el entorno.
     */
    public static function init(): void
    {
        $secret = $_ENV['JWT_SECRET']
            ?? throw new RuntimeException('JWT_SECRET no definido en .env');

        self::$config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secret)
        );
    }

    private static function getConfig(): Configuration
    {
        if (self::$config === null) {
            throw new LogicException('JwtUtil::init() debe llamarse antes de usar JwtUtil');
        }
        return self::$config;
    }

    /**
     * Genera un token JWT para el autónomo con el id dado.
     * El claim 'sub' lleva el id del autónomo como string (estándar JWT).
     *
     * @param int $autonomoId ID del autónomo autenticado.
     * @return string Token JWT firmado listo para incluir en el header Authorization.
     */
    public static function generate(int $autonomoId): string
    {
        $config  = self::getConfig();
        $now     = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $expSecs = (int)($_ENV['JWT_EXPIRATION'] ?? 86400);

        $token = $config->builder()
            ->issuedBy('facturo-php')
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify("+{$expSecs} seconds"))
            ->relatedTo((string)$autonomoId)
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }

    /**
     * Valida el token y retorna el autonomoId si es válido, null si no.
     * No lanza excepción — devuelve null para que el middleware decida la respuesta.
     *
     * @param string $tokenString Token JWT en formato string (sin el prefijo "Bearer ").
     * @return int|null ID del autónomo si el token es válido, null si es inválido o expirado.
     */
    public static function validate(string $tokenString): ?int
    {
        $config = self::getConfig();

        try {
            /** @var UnencryptedToken $token */
            $token = $config->parser()->parse($tokenString);

            $config->validator()->assert($token,
                new SignedWith($config->signer(), $config->signingKey()),
                new StrictValidAt(new class implements ClockInterface {
                    public function now(): DateTimeImmutable {
                        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
                    }
                }),
                new IssuedBy('facturo-php')
            );

            return (int)$token->claims()->get('sub');

        } catch (Throwable) {
            return null;
        }
    }
}