/**
 * Lee el backend seleccionado en la pantalla de login.
 * Si no hay nada guardado, usa Java por defecto.
 */
export const BACKENDS = {
    java: 'http://localhost:8080',
    php:  'http://localhost:8000',
};

export const API_BASE = localStorage.getItem('api_backend') ?? BACKENDS.java;
