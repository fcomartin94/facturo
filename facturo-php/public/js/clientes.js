import { api }           from './api.js';
import { requireAuth, logout, getAutonomo } from './auth.js';

requireAuth();

// Mostrar nombre del autónomo en nav
const autonomo = getAutonomo();
document.getElementById('user-name').textContent =
    autonomo ? `${autonomo.nombre} ${autonomo.apellidos}` : '';
document.getElementById('btn-logout').addEventListener('click', logout);

const tbody    = document.getElementById('clientes-tbody');
const formEl   = document.getElementById('form-cliente');
const modalEl  = document.getElementById('modal-cliente');
let   editId   = null;

// Cargar y renderizar clientes
async function loadClientes() {
    const clientes = await api.getClientes();
    tbody.innerHTML = clientes.map(c => `
        <tr>
          <td>${c.nombre}</td>
          <td>${c.nif}</td>
          <td>${c.email ?? '—'}</td>
          <td>${c.telefono ?? '—'}</td>
          <td>
            <button class="btn btn-sm btn-primary" onclick="editCliente(${c.id})">Editar</button>
            <button class="btn btn-sm btn-danger"  onclick="deleteCliente(${c.id})">Eliminar</button>
          </td>
        </tr>
    `).join('');
}

// Abrir modal para nuevo cliente
document.getElementById('btn-new-cliente').addEventListener('click', () => {
    editId = null;
    formEl.reset();
    document.getElementById('modal-title').textContent = 'Nuevo cliente';
    modalEl.style.display = 'flex';
});

document.getElementById('btn-modal-close').addEventListener('click', () => {
    modalEl.style.display = 'none';
});

// Guardar (crear o actualizar)
formEl.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
        nombre:   document.getElementById('c-nombre').value,
        nif:      document.getElementById('c-nif').value,
        email:    document.getElementById('c-email').value,
        telefono: document.getElementById('c-telefono').value,
    };
    try {
        if (editId) {
            await api.updateCliente(editId, data);
        } else {
            await api.createCliente(data);
        }
        modalEl.style.display = 'none';
        await loadClientes();
    } catch (err) {
        document.getElementById('form-error').textContent = err.message;
    }
});

// Editar
window.editCliente = async (id) => {
    const c = await api.getCliente(id);
    editId = id;
    document.getElementById('c-nombre').value   = c.nombre;
    document.getElementById('c-nif').value      = c.nif;
    document.getElementById('c-email').value    = c.email ?? '';
    document.getElementById('c-telefono').value = c.telefono ?? '';
    document.getElementById('modal-title').textContent = 'Editar cliente';
    modalEl.style.display = 'flex';
};

// Eliminar
window.deleteCliente = async (id) => {
    if (!confirm('¿Eliminar este cliente?')) return;
    await api.deleteCliente(id);
    await loadClientes();
};

loadClientes();