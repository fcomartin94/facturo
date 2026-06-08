import { api } from './api.js';
import { BACKENDS } from './config.js';

// ── Selector de backend ───────────────────────────────────
const backendSelect = document.getElementById('backend-select');

if (backendSelect) {
    // Restaurar la selección guardada (o Java por defecto)
    const saved = localStorage.getItem('api_backend') ?? BACKENDS.java;
    backendSelect.value = saved;

    // Guardar en localStorage al cambiar — config.js lo leerá en el próximo módulo import
    backendSelect.addEventListener('change', () => {
        localStorage.setItem('api_backend', backendSelect.value);
    });
}

// ── Helpers de sesión ─────────────────────────────────────
function saveAuth(token, autonomo) {
    localStorage.setItem('jwt_token', token);
    localStorage.setItem('autonomo', JSON.stringify(autonomo));
}

export function getAutonomo() {
    const raw = localStorage.getItem('autonomo');
    return raw ? JSON.parse(raw) : null;
}

export function logout() {
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('autonomo');
    window.location.href = 'index.html';
}

// Redirige a index.html si no hay sesión activa
export function requireAuth() {
    if (!localStorage.getItem('jwt_token')) {
        window.location.href = 'index.html';
    }
}

// ── Formulario de login ────────────────────────────────────
document.getElementById('form-login')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Guardar el backend seleccionado antes de lanzar la petición
    if (backendSelect) {
        localStorage.setItem('api_backend', backendSelect.value);
    }

    const email    = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    try {
        const result = await api.login({ email, password });
        saveAuth(result.token, result.autonomo);
        window.location.href = 'facturas.html';
    } catch (err) {
        document.getElementById('login-error').textContent = err.message;
    }
});

// ── Formulario de registro ────────────────────────────────
document.getElementById('form-register')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (backendSelect) {
        localStorage.setItem('api_backend', backendSelect.value);
    }

    const data = {
        email:     document.getElementById('reg-email').value,
        password:  document.getElementById('reg-password').value,
        nombre:    document.getElementById('reg-nombre').value,
        apellidos: document.getElementById('reg-apellidos').value,
        nif:       document.getElementById('reg-nif').value,
    };

    try {
        const result = await api.register(data);
        saveAuth(result.token, result.autonomo);
        window.location.href = 'facturas.html';
    } catch (err) {
        document.getElementById('register-error').textContent = err.message;
    }
});
