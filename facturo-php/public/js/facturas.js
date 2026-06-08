import { api }           from './api.js';
import { requireAuth, logout, getAutonomo } from './auth.js';

requireAuth();

const autonomo = getAutonomo();
document.getElementById('user-name').textContent =
    autonomo ? `${autonomo.nombre} ${autonomo.apellidos}` : '';
document.getElementById('btn-logout').addEventListener('click', logout);

const tbody    = document.getElementById('facturas-tbody');
const modalEl  = document.getElementById('modal-factura');
const formEl   = document.getElementById('form-factura');
const lineasEl = document.getElementById('lineas-container');
const ESTADOS  = ['BORRADOR','EMITIDA','PAGADA','VENCIDA','CANCELADA'];

// ── Helpers ───────────────────────────────────────────────
function badgeEstado(estado) {
    return `<span class="badge badge-${estado.toLowerCase()}">${estado}</span>`;
}

function fmt(n) {
    return Number(n).toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });
}

// ── Lista de facturas ─────────────────────────────────────
async function loadFacturas() {
    const facturas = await api.getFacturas();
    if (!facturas.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#64748b;padding:32px">Sin facturas</td></tr>';
        return;
    }
    tbody.innerHTML = facturas.map(f => `
        <tr>
          <td>${f.numeroFactura}</td>
          <td>${f.fechaEmision}</td>
          <td>${f.total ? fmt(f.total) : '—'}</td>
          <td>${badgeEstado(f.estado)}</td>
          <td style="white-space:nowrap">
            <select class="estado-select" data-id="${f.id}"
              style="background:#1e293b;color:#e2e8f0;border:1px solid #334155;padding:3px 6px;border-radius:4px;margin-right:6px">
              ${ESTADOS.map(e => `<option value="${e}" ${e===f.estado?'selected':''}>${e}</option>`).join('')}
            </select>
            <button class="btn btn-sm btn-primary" onclick="downloadPdf(${f.id})">PDF</button>
          </td>
        </tr>
    `).join('');

    document.querySelectorAll('.estado-select').forEach(sel => {
        sel.addEventListener('change', async () => {
            await api.updateEstado(Number(sel.dataset.id), sel.value);
            await loadFacturas();
        });
    });
}

// ── Líneas dinámicas ──────────────────────────────────────
function calcImporte(row) {
    const q = parseFloat(row.querySelector('.l-cantidad').value) || 0;
    const p = parseFloat(row.querySelector('.l-precio').value)   || 0;
    row.querySelector('.l-importe').textContent = fmt(q * p);
    recalcTotal();
}

function recalcTotal() {
    let base = 0;
    document.querySelectorAll('.linea-row').forEach(row => {
        const q = parseFloat(row.querySelector('.l-cantidad').value) || 0;
        const p = parseFloat(row.querySelector('.l-precio').value)   || 0;
        base += q * p;
    });
    document.getElementById('preview-base').textContent  = fmt(base);
    document.getElementById('preview-iva').textContent   = fmt(base * 0.21);
    document.getElementById('preview-irpf').textContent  = fmt(base * 0.15);
    document.getElementById('preview-total').textContent = fmt(base + base * 0.21 - base * 0.15);
}

function addLinea() {
    const row = document.createElement('div');
    row.className  = 'linea-row';
    row.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;align-items:center';
    row.innerHTML = `
        <input class="l-concepto" placeholder="Concepto *" required
          style="flex:2;background:#0f172a;border:1px solid #334155;color:#e2e8f0;padding:6px 8px;border-radius:4px">
        <input class="l-cantidad" type="number" min="0.01" step="0.01" placeholder="Cant." required
          style="width:80px;background:#0f172a;border:1px solid #334155;color:#e2e8f0;padding:6px 8px;border-radius:4px">
        <input class="l-precio" type="number" min="0.01" step="0.01" placeholder="Precio" required
          style="width:100px;background:#0f172a;border:1px solid #334155;color:#e2e8f0;padding:6px 8px;border-radius:4px">
        <span class="l-importe"
          style="width:90px;text-align:right;color:#94a3b8;font-size:13px">0,00 €</span>
        <button type="button" class="btn btn-sm btn-danger l-remove">✕</button>
    `;
    row.querySelector('.l-remove').addEventListener('click', () => { row.remove(); recalcTotal(); });
    row.querySelector('.l-cantidad').addEventListener('input', () => calcImporte(row));
    row.querySelector('.l-precio').addEventListener('input',   () => calcImporte(row));
    lineasEl.appendChild(row);
}

// ── Abrir modal ───────────────────────────────────────────
document.getElementById('btn-new-factura').addEventListener('click', async () => {
    formEl.reset();
    lineasEl.innerHTML = '';
    document.getElementById('form-error').textContent = '';
    document.getElementById('f-fecha').value = new Date().toISOString().slice(0, 10);
    recalcTotal();

    // Poblar selector de clientes con los del autónomo autenticado
    const clientes = await api.getClientes();
    const sel = document.getElementById('f-cliente');
    sel.innerHTML = '<option value="">— Selecciona cliente —</option>' +
        clientes.map(c => `<option value="${c.id}">${c.nombre} (${c.nif})</option>`).join('');

    addLinea(); // Una línea por defecto
    modalEl.style.display = 'flex';
});

document.getElementById('btn-add-linea').addEventListener('click', addLinea);

document.getElementById('btn-modal-close').addEventListener('click', () => {
    modalEl.style.display = 'none';
});

// ── Crear factura ─────────────────────────────────────────
formEl.addEventListener('submit', async (e) => {
    e.preventDefault();
    const lineas = [...document.querySelectorAll('.linea-row')].map(row => ({
        concepto:       row.querySelector('.l-concepto').value,
        cantidad:       parseFloat(row.querySelector('.l-cantidad').value),
        precioUnitario: parseFloat(row.querySelector('.l-precio').value),
    }));

    try {
        await api.createFactura({
            clienteId:    Number(document.getElementById('f-cliente').value),
            fechaEmision: document.getElementById('f-fecha').value,
            lineas,
        });
        modalEl.style.display = 'none';
        await loadFacturas();
    } catch (err) {
        document.getElementById('form-error').textContent = err.message;
    }
});

// ── Descargar PDF ─────────────────────────────────────────
window.downloadPdf = async (id) => {
    const blob = await api.downloadPdf(id);
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `factura-${id}.pdf`;
    a.click();
    URL.revokeObjectURL(url);
};

loadFacturas();