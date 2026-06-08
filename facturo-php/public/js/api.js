const API_BASE = 'http://localhost:8080';

/**
 * Realiza una llamada a la API añadiendo automáticamente el JWT si existe.
 * Lanza un Error con el mensaje del servidor si la respuesta no es 2xx.
 */
async function apiFetch(endpoint, options = {}) {
    const token = localStorage.getItem('jwt_token');

    const headers = {
        'Content-Type': 'application/json',
        ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
        ...(options.headers ?? {}),
    };

    const response = await fetch(`${API_BASE}${endpoint}`, {
        ...options,
        headers,
    });

    // El PDF no es JSON — devolver el blob directamente
    if (response.headers.get('Content-Type')?.includes('application/pdf')) {
        if (!response.ok) throw new Error('Error al descargar PDF');
        return response.blob();
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        // Extraer mensaje de error del formato estándar de la API
        const message = data.error
            ?? Object.values(data.errors ?? {}).join(', ')
            ?? `Error HTTP ${response.status}`;
        throw new Error(message);
    }

    return data;
}

export const api = {
    // Auth
    register: (body)     => apiFetch('/api/auth/register', { method: 'POST', body: JSON.stringify(body) }),
    login:    (body)     => apiFetch('/api/auth/login',    { method: 'POST', body: JSON.stringify(body) }),

    // Clientes
    getClientes:         () => apiFetch('/api/clientes'),
    getCliente:   (id)   => apiFetch(`/api/clientes/${id}`),
    createCliente: (b)   => apiFetch('/api/clientes',      { method: 'POST',   body: JSON.stringify(b) }),
    updateCliente: (id, b)=> apiFetch(`/api/clientes/${id}`,{ method: 'PUT',   body: JSON.stringify(b) }),
    deleteCliente: (id)  => apiFetch(`/api/clientes/${id}`,{ method: 'DELETE' }),

    // Facturas
    getFacturas:         () => apiFetch('/api/facturas'),
    getFactura:   (id)   => apiFetch(`/api/facturas/${id}`),
    createFactura: (b)   => apiFetch('/api/facturas',      { method: 'POST',   body: JSON.stringify(b) }),
    updateEstado: (id, e)=> apiFetch(`/api/facturas/${id}/estado`, { method: 'PATCH', body: JSON.stringify({ estado: e }) }),
    downloadPdf:  (id)   => apiFetch(`/api/facturas/${id}/pdf`),
};