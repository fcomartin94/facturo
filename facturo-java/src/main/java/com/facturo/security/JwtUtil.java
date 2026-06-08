package com.facturo.security;

import io.jsonwebtoken.Claims;
import io.jsonwebtoken.Jwts;
import io.jsonwebtoken.security.Keys;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.security.core.userdetails.UserDetails;
import org.springframework.stereotype.Component;

import javax.crypto.SecretKey;
import java.util.Base64;
import java.util.Date;

/**
 * JWT utility component for Facturo.
 *
 * <p>Generates and validates JSON Web Tokens used for stateless authentication.
 * Tokens are signed with HMAC-SHA256. The secret is injected as a Base64-encoded
 * string via {@code facturo.jwt.secret}; expiration is controlled by
 * {@code facturo.jwt.expiration-ms}.</p>
 *
 * <p>Used by {@link JwtAuthFilter} to authenticate every API request that
 * carries an {@code Authorization: Bearer <token>} header.</p>
 */
@Component
public class JwtUtil {

    @Value("${facturo.jwt.secret}")
    private String secret;

    @Value("${facturo.jwt.expiration-ms}")
    private long expirationMs;

    /**
     * Decodes the Base64-encoded secret and returns an HMAC-SHA signing key.
     *
     * @return a {@link SecretKey} suitable for signing and verifying JWTs
     */
    private SecretKey getSigningKey() {
        byte[] keyBytes = Base64.getDecoder().decode(secret);
        return Keys.hmacShaKeyFor(keyBytes);
    }

    /**
     * Generates a signed JWT token for the given user.
     *
     * <p>The subject is set to {@link UserDetails#getUsername()} (the freelancer's
     * email). Issued-at and expiration claims are set automatically.</p>
     *
     * @param userDetails the authenticated user principal
     * @return a compact, signed JWT string
     */
    public String generateToken(UserDetails userDetails) {
        return Jwts.builder()
                .subject(userDetails.getUsername())
                .issuedAt(new Date())
                .expiration(new Date(System.currentTimeMillis() + expirationMs))
                .signWith(getSigningKey())
                .compact();
    }

    /**
     * Extracts the subject (email / username) from a JWT token.
     *
     * @param token a compact JWT string
     * @return the subject claim value
     */
    public String extractUsername(String token) {
        return extractClaims(token).getSubject();
    }

    /**
     * Returns {@code true} if the token's subject matches the given user
     * and the token has not expired.
     *
     * @param token       the JWT string to validate
     * @param userDetails the user principal to validate against
     * @return {@code true} if the token is valid for this user
     */
    public boolean isTokenValid(String token, UserDetails userDetails) {
        String username = extractUsername(token);
        return username.equals(userDetails.getUsername()) && !isTokenExpired(token);
    }

    /** Returns {@code true} if the token's expiration date is before the current time. */
    private boolean isTokenExpired(String token) {
        return extractClaims(token).getExpiration().before(new Date());
    }

    /**
     * Parses and verifies the JWT, returning its claims payload.
     *
     * @param token a compact JWT string
     * @return the parsed {@link Claims}
     * @throws io.jsonwebtoken.JwtException if the token is malformed, expired, or has an invalid signature
     */
    private Claims extractClaims(String token) {
        return Jwts.parser()
                .verifyWith(getSigningKey())
                .build()
                .parseSignedClaims(token)
                .getPayload();
    }
}
