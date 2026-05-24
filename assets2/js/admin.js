// ============================================================
//  admin.js — Panel de Administración
//  Cambios: CSRF en todas las peticiones, sanitización XSS,
//  navegación por data-panel, grupos/salones con persistencia
//  AJAX, cargarGrupos/cargarSalones desde backend.
// ============================================================

/* ── CONSTANTES ── */
const COLORS = {
    c0: '#c8a96e', c1: '#64a0dc', c2: '#78c88c', c3: '#c878b4',
    c4: '#dc9664', c5: '#a08cdc', c6: '#60c8c0', c7: '#e0b060'
};
const DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
const DIAS_LABEL = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

/* ── ESTADO LOCAL (caché en memoria, fuente de verdad = BD) ── */
let grupos = [];   // [{ id, nombre, desc, prof_id, prof_nombre }]
let salonesData = [];   // [{ id, nombre, capacidad }]
let schedData = {};   // { grupo_id: [ bloque, ... ] }
let selectedColor = 'c0';
let editingBlock = null;
let clickedCell = null;

/* ── UTILIDADES DE SEGURIDAD ── */

/**
 * Escapa caracteres peligrosos para evitar XSS al insertar
 * texto en innerHTML. Úsala siempre que muestres datos del servidor.
 */
function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Lee el token CSRF del <meta name="csrf-token"> generado por PHP.
 * Se incluye en cada petición POST/PUT/DELETE.
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/**
 * Wrapper de fetch que inyecta el CSRF token automáticamente
 * en todas las peticiones que modifican datos (POST/PUT/DELETE).
 */
async function apiFetch(url, options = {}) {
    const method = (options.method || 'GET').toUpperCase();
    const headers = { 'Content-Type': 'application/json', ...(options.headers || {}) };

    if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
        headers['X-CSRF-Token'] = getCsrfToken();
    }

    const resp = await fetch(url, { ...options, headers });
    if (!resp.ok) {
        const txt = await resp.text().catch(() => '');
        throw new Error(`HTTP ${resp.status}: ${txt}`);
    }
    return resp.json();
}

/* ══════════════════════════════════════════════════════════════
   NAVEGACIÓN
   Usa data-panel en lugar de onclick="go(...)" hardcodeado
   ══════════════════════════════════════════════════════════════ */
const panelMeta = {
    home: { title: 'Resumen', sub: 'Panel de administración' },
    horario: { title: 'Gestión de Horarios', sub: 'Crea y edita bloques por grupo' },
    usuarios: { title: 'Usuarios', sub: 'Gestiona cuentas y roles' },
    grupos: { title: 'Grupos', sub: 'Organiza grupos y asignaciones' },
    salones: { title: 'Salones', sub: 'Administra aulas y disponibilidad' },
    solicitudes: { title: 'Solicitudes de Rol', sub: 'Aprueba o rechaza cambios de rol' },
    notificaciones: { title: 'Notificaciones', sub: 'Mensajes del sistema' },
};

function go(name, activeBtn = null) {
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.ni').forEach(n => n.classList.remove('active'));

    const panel = document.getElementById('panel-' + name);
    if (panel) panel.classList.add('active');

    // Activar botón del sidebar: el recibido, o el que tiene data-panel="name"
    const btn = activeBtn ?? document.querySelector(`.ni[data-panel="${name}"]`);
    if (btn) btn.classList.add('active');

    const m = panelMeta[name] || { title: name, sub: '' };
    document.getElementById('tb-title').textContent = m.title;
    document.getElementById('tb-sub').textContent = m.sub;

    if (name === 'notificaciones') {
        const b = document.getElementById('notif-badge');
        if (b) b.textContent = '0';
    }
    if (name === 'solicitudes') cargarSolicitudes();
    if (name === 'horario') renderSchedPanel();
}

/* ── Delegación de eventos para sidebar (data-panel) ── */
document.addEventListener('click', e => {
    const btn = e.target.closest('[data-panel]');
    if (btn) { go(btn.dataset.panel, btn); return; }

    // Botón "Ir a Grupos" del estado vacío de horarios
    const goto = e.target.closest('[data-goto]');
    if (goto) {
        const target = goto.dataset.goto;
        go(target, document.querySelector(`.ni[data-panel="${target}"]`));
    }
});

/* ══════════════════════════════════════════════════════════════
   ESTADÍSTICAS Y ACTIVIDAD
   ══════════════════════════════════════════════════════════════ */
async function cargarEstadisticas() {
    try {
        const data = await apiFetch('api/v1/admin/stats.php');
        const map = {
            total_usuarios: 'count-usuarios',
            total_profesores: 'count-profesores',
            total_solicitudes: 'count-solicitudes',
            total_salones: 'count-salones',
            total_horarios: 'count-horarios',
            total_grupos: 'count-grupos',
        };
        Object.entries(map).forEach(([k, id]) => {
            const el = document.getElementById(id);
            if (el && data[k] !== undefined) el.textContent = data[k];
        });
    } catch (e) { console.error('Error stats:', e); }
}

async function cargarActividad() {
    const lista = document.querySelector('.activity-list');
    if (!lista) return;
    try {
        const data = await apiFetch('api/v1/admin/actividad.php');
        if (!data || data.length === 0) {
            lista.innerHTML = '<p style="color:var(--muted);padding:16px;font-size:13px">No hay actividad reciente.</p>';
            return;
        }
        // esc() en cada campo para evitar XSS
        lista.innerHTML = data.map(item => `
            <div class="notif-item">
                <span class="notif-dot ${item.estado !== 'pendiente' ? 'read' : ''}"></span>
                <div class="notif-body">
                    <div class="notif-msg">
                        <strong>${esc(item.nombre)}</strong> solicitó cambio de rol a
                        <strong>${esc(item.nombre_rol)}</strong>
                    </div>
                    <div class="notif-time">Estado: ${esc(item.estado)} · ${esc(item.created_at)}</div>
                </div>
            </div>`).join('');
    } catch (e) { console.error('Error actividad:', e); }
}

/* ══════════════════════════════════════════════════════════════
   USUARIOS
   ══════════════════════════════════════════════════════════════ */
async function cargarUsuarios() {
    const tbody = document.getElementById('usr-tbody');
    if (!tbody) return;
    try {
        const data = await apiFetch('api/v1/admin/usuarios.php');
        if (!data.length) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:20px;color:var(--muted)">No hay usuarios registrados.</td></tr>';
            return;
        }
        const badgeClass = id => id == 1 ? 'badge-admin' : id == 2 ? 'badge-prof' : 'badge-est';
        // esc() en todos los campos para evitar XSS
        tbody.innerHTML = data.map(u => `
            <tr>
                <td>${esc(u.nombre)} ${esc(u.apellido)}</td>
                <td>${esc(u.nickname || u.usuario)}</td>
                <td>${esc(u.correo)}</td>
                <td><span class="badge ${badgeClass(u.rol_id)}">${esc(u.rol)}</span></td>
                <td><span class="badge badge-act">Activo</span></td>
                <td>
                    <div class="actions-cell">
                        <button class="btn btn-o btn-sm" onclick="editUser(${Number(u.id)})">Editar</button>
                        <button class="btn btn-d btn-sm" onclick="desactivarUsuario(${Number(u.id)}, this)">Desactivar</button>
                    </div>
                </td>
            </tr>`).join('');
    } catch (e) {
        console.error('Error al cargar usuarios:', e);
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--danger)">Error al conectar con el servidor.</td></tr>';
    }
}

async function saveUsuario() {
    const nombre = document.getElementById('usr-nombre').value.trim();
    const apellido = document.getElementById('usr-apellido').value.trim();
    const nick = document.getElementById('usr-nick').value.trim();
    const correo = document.getElementById('usr-correo').value.trim();
    const rol = document.getElementById('usr-rol').value;
    const pass = document.getElementById('usr-pass').value;

    if (!nombre || !correo || !pass) {
        alert('Nombre, correo y contraseña son obligatorios.');
        return;
    }

    try {
        // CSRF se inyecta automáticamente en apiFetch para POST
        const data = await apiFetch('api/v1/admin/usuarios.php', {
            method: 'POST',
            body: JSON.stringify({ nombre, apellido, nickname: nick, correo, rol_id: rol, password: pass })
        });
        if (data.ok !== false) {
            closeModal('modal-usuario');
            limpiarFormUsuario();
            cargarUsuarios();
        } else {
            alert(data.mensaje || 'Error al guardar el usuario.');
        }
    } catch (e) {
        console.error(e);
        alert('Error de conexión al guardar usuario.');
    }
}

function limpiarFormUsuario() {
    ['usr-nombre', 'usr-apellido', 'usr-nick', 'usr-correo', 'usr-pass'].forEach(id => {
        document.getElementById(id).value = '';
    });
}

function editUser(id) {
    // TODO: abrir modal pre-llenado con datos del usuario
    alert('Función de edición próximamente (ID: ' + id + ')');
}

async function desactivarUsuario(id, btn) {
    if (!confirm('¿Desactivar este usuario?')) return;
    try {
        await apiFetch('api/v1/admin/usuarios.php', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
        cargarUsuarios();
    } catch (e) {
        alert('Error al desactivar el usuario.');
    }
}

/* ══════════════════════════════════════════════════════════════
   SOLICITUDES
   ══════════════════════════════════════════════════════════════ */
async function cargarSolicitudes() {
    try {
        const data = await apiFetch('api/v1/admin/solicitudes.php');
        const badge = document.getElementById('sol-badge');
        const tbody = document.getElementById('sol-tbody');
        const pending = data.filter(d => d.estado === 'pendiente');

        if (badge) badge.textContent = pending.length;
        if (!data || !data.length) return;

        // esc() en todos los campos para evitar XSS
        tbody.innerHTML = data.map(s => `
            <tr data-id="${Number(s.id)}">
                <td>${esc(s.nombre)} ${esc(s.apellido ?? '')}</td>
                <td><span class="badge">${esc(s.rol_actual ?? '—')}</span></td>
                <td><span class="badge badge-prof">${esc(s.nombre_rol)}</span></td>
                <td>${esc(s.created_at)}</td>
                <td><span class="badge ${s.estado === 'pendiente' ? 'badge-pend' : s.estado === 'aprobado' ? 'badge-act' : 'badge-rej'}">${esc(s.estado)}</span></td>
                <td>
                    <div class="actions-cell">
                        ${s.estado === 'pendiente'
                ? `<button class="btn btn-s btn-sm" onclick="responderSolicitud(${Number(s.id)}, 'aprobado')">Aprobar</button>
                               <button class="btn btn-d btn-sm" onclick="responderSolicitud(${Number(s.id)}, 'rechazado')">Rechazar</button>`
                : `<span style="color:var(--muted);font-size:12px">Procesada</span>`
            }
                    </div>
                </td>
            </tr>`).join('');
    } catch (e) { console.error('Error solicitudes:', e); }
}

/* ══════════════════════════════════════════════════════════════
   GRUPOS — ahora persisten en BD via AJAX
   ══════════════════════════════════════════════════════════════ */
async function cargarGrupos() {
    try {
        const data = await apiFetch('api/v1/admin/grupos.php');
        grupos = data;
        renderTablaGrupos();
        // Actualizar stat
        const el = document.getElementById('count-grupos');
        if (el) el.textContent = grupos.length;
    } catch (e) { console.error('Error al cargar grupos:', e); }
}

function renderTablaGrupos() {
    const tbody = document.getElementById('grp-tbody');
    if (!tbody) return;
    if (!grupos.length) {
        tbody.innerHTML = '<tr id="grp-empty-row"><td colspan="5" style="text-align:center;color:var(--muted);padding:30px">No hay grupos creados. Usa <strong>+ Nuevo grupo</strong> para comenzar.</td></tr>';
        return;
    }
    // esc() en todos los campos para evitar XSS
    tbody.innerHTML = grupos.map(g => `
        <tr data-gid="${Number(g.id)}">
            <td><strong>${esc(g.nombre)}</strong></td>
            <td>${esc(g.descripcion || '—')}</td>
            <td>${esc(g.total_miembros ?? 0)}</td>
            <td>${esc(g.prof_nombre || '—')}</td>
            <td>
                <div class="actions-cell">
                    <button class="btn btn-o btn-sm" onclick="irAHorarioGrupo(${Number(g.id)})">Ver horario</button>
                    <button class="btn btn-d btn-sm" onclick="eliminarGrupo(${Number(g.id)}, this)">Eliminar</button>
                </div>
            </td>
        </tr>`).join('');
}

async function saveGrupo() {
    const nombre = document.getElementById('grp-nombre').value.trim();
    const desc = document.getElementById('grp-desc').value.trim();
    const profId = document.getElementById('grp-prof').value;

    if (!nombre) { alert('El nombre del grupo es obligatorio.'); return; }

    try {
        // CSRF se inyecta automáticamente en apiFetch para POST
        const data = await apiFetch('api/v1/admin/grupos.php', {
            method: 'POST',
            body: JSON.stringify({ nombre, descripcion: desc, prof_id: profId || null })
        });
        if (data.ok === false) { alert(data.mensaje || 'Error al guardar.'); return; }

        closeModal('modal-grupo');
        document.getElementById('grp-nombre').value = '';
        document.getElementById('grp-desc').value = '';
        cargarGrupos();     // refrescar tabla desde BD
        cargarEstadisticas();
    } catch (e) {
        console.error(e);
        alert('Error de conexión al guardar el grupo.');
    }
}

async function eliminarGrupo(id, btn) {
    if (!confirm('¿Eliminar este grupo? También se eliminarán sus bloques de horario.')) return;
    try {
        await apiFetch('api/v1/admin/grupos.php', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
        delete schedData[id];
        cargarGrupos();
        cargarEstadisticas();
    } catch (e) {
        alert('Error al eliminar el grupo.');
    }
}

function irAHorarioGrupo(id) {
    // Navegar a Horario y preseleccionar el grupo
    go('horario', document.querySelector('.ni[data-panel="horario"]'));
    const sel = document.getElementById('sched-grupo');
    if (sel) { sel.value = id; renderSched(); }
}

/* ══════════════════════════════════════════════════════════════
   SALONES — ahora persisten en BD via AJAX
   ══════════════════════════════════════════════════════════════ */
async function cargarSalones() {
    try {
        const data = await apiFetch('api/v1/admin/salones.php');
        salonesData = data;
        renderTablaSalones();
        const el = document.getElementById('count-salones');
        if (el) el.textContent = salonesData.length;
    } catch (e) { console.error('Error al cargar salones:', e); }
}

function renderTablaSalones() {
    const tbody = document.getElementById('sal-tbody');
    const grid = document.getElementById('avail-grid');
    if (!tbody) return;

    if (!salonesData.length) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:30px">No hay salones registrados aún.</td></tr>';
        if (grid) grid.innerHTML = '<p style="color:var(--muted);font-size:13px;grid-column:1/-1">No hay salones registrados aún.</p>';
        return;
    }

    // esc() en todos los campos para evitar XSS
    tbody.innerHTML = salonesData.map(s => `
        <tr data-sid="${Number(s.id)}">
            <td>${esc(s.nombre)}</td>
            <td>${esc(s.capacidad)}</td>
            <td><span class="badge badge-act">Disponible</span></td>
            <td>
                <div class="actions-cell">
                    <button class="btn btn-d btn-sm" onclick="eliminarSalon(${Number(s.id)}, this)">Eliminar</button>
                </div>
            </td>
        </tr>`).join('');

    if (grid) {
        grid.innerHTML = salonesData.map(s => `
            <div class="avail-card" data-sid="${Number(s.id)}">
                <h4>${esc(s.nombre)}</h4>
                <div class="cap">Capacidad: ${esc(s.capacidad)}</div>
                <div class="avail-bar-wrap"><div class="avail-bar" style="width:0%"></div></div>
            </div>`).join('');
    }
}

async function saveSalon() {
    const nombre = document.getElementById('sal-nombre').value.trim();
    const cap = parseInt(document.getElementById('sal-cap').value) || 0;
    if (!nombre) { alert('El nombre del salón es obligatorio.'); return; }

    try {
        // CSRF se inyecta automáticamente en apiFetch para POST
        const data = await apiFetch('api/v1/admin/salones.php', {
            method: 'POST',
            body: JSON.stringify({ nombre, capacidad: cap })
        });
        if (data.ok === false) { alert(data.mensaje || 'Error al guardar.'); return; }

        closeModal('modal-salon');
        document.getElementById('sal-nombre').value = '';
        document.getElementById('sal-cap').value = '';
        cargarSalones();
        cargarEstadisticas();
    } catch (e) {
        alert('Error de conexión al guardar el salón.');
    }
}

async function eliminarSalon(id, btn) {
    const salon = salonesData.find(s => s.id == id);
    if (!confirm(`¿Eliminar el salón "${salon?.nombre ?? id}"?`)) return;
    try {
        await apiFetch('api/v1/admin/salones.php', {
            method: 'DELETE',
            body: JSON.stringify({ id })
        });
        cargarSalones();
    } catch (e) {
        alert('Error al eliminar el salón.');
    }
}

/* ══════════════════════════════════════════════════════════════
   HORARIO
   ══════════════════════════════════════════════════════════════ */
function renderSchedPanel() {
    const noGroups = document.getElementById('sched-no-groups');
    const schedCont = document.getElementById('sched-content');
    if (!grupos.length) {
        if (noGroups) noGroups.style.display = 'block';
        if (schedCont) schedCont.style.display = 'none';
        return;
    }
    if (noGroups) noGroups.style.display = 'none';
    if (schedCont) schedCont.style.display = 'block';

    const sel = document.getElementById('sched-grupo');
    // esc() en los nombres para evitar XSS en el select
    sel.innerHTML = grupos.map(g => `<option value="${Number(g.id)}">${esc(g.nombre)}</option>`).join('');
    cargarBloques(currentGrupo());
}

function currentGrupo() { return document.getElementById('sched-grupo').value; }

function buildHoras() {
    const arr = [];
    for (let h = 6; h < 20; h++) arr.push(String(h).padStart(2, '0') + ':00');
    return arr;
}

function getBlocks(dia, hora) {
    return (schedData[currentGrupo()] || []).filter(b => b.dia === dia && b.hora_inicio === hora);
}

async function cargarBloques(grupoId) {
    if (!grupoId) return;
    try {
        const data = await apiFetch(`api/v1/admin/horarios.php?grupo_id=${encodeURIComponent(grupoId)}`);
        schedData[grupoId] = data;
        renderSched();
    } catch (e) { console.error('Error al cargar bloques:', e); }
}

function renderSched() {
    const horas = buildHoras();
    const head = document.getElementById('sched-head');
    const body = document.getElementById('sched-body');
    if (!head || !body) return;

    head.innerHTML = '<tr><th>Hora</th>' + DIAS_LABEL.map(d => `<th>${d}</th>`).join('') + '</tr>';
    const occ = {};
    body.innerHTML = '';

    horas.forEach(hora => {
        const tr = document.createElement('tr');
        const td0 = document.createElement('td');
        td0.className = 'time-lbl';
        td0.textContent = hora;
        tr.appendChild(td0);

        DIAS.forEach(dia => {
            const key = `${dia}-${hora}`;
            if (occ[key]) return;
            const blocks = getBlocks(dia, hora);
            const td = document.createElement('td');
            td.className = 'sc';

            if (blocks.length) {
                const b = blocks[0];
                const sh = parseInt(b.hora_inicio);
                const eh = parseInt(b.hora_fin);
                const span = eh - sh;
                if (span > 1) {
                    td.rowSpan = span;
                    for (let i = 1; i < span; i++)
                        occ[`${dia}-${String(sh + i).padStart(2, '0')}:00`] = true;
                }
                const col = COLORS[b.color] || COLORS.c0;
                const colA = col + '28';
                const idx = (schedData[currentGrupo()] || []).indexOf(b);
                // esc() en nombre, salon y profesor del bloque
                td.innerHTML = `
                    <div class="blk" style="background:${colA};border-left:3px solid ${col};color:${col}" onclick="editBlock(${idx})">
                        <span class="blk-name">${esc(b.nombre)}</span>
                        <span class="blk-info">📍 ${esc(b.salon)} · ${esc(b.hora_inicio)}–${esc(b.hora_fin)}</span>
                        <span class="blk-info">${esc(b.profesor)}</span>
                        <button class="blk-del" onclick="deleteBlock(event,${idx})">✕</button>
                    </div>`;
            } else {
                td.innerHTML = `<span class="add-hint">+</span>`;
                td.onclick = () => { clickedCell = { dia, hora }; abrirModalBloque(true); };
            }
            tr.appendChild(td);
        });
        body.appendChild(tr);
    });
}

/* ── MODAL BLOQUE ── */
function abrirModalBloque(fromCell = false) {
    editingBlock = null;
    document.getElementById('bloque-modal-title').textContent = 'Agregar bloque';
    document.getElementById('blk-nombre').value = '';
    document.getElementById('blk-inicio').value = fromCell && clickedCell ? clickedCell.hora : '07:00';
    document.getElementById('blk-fin').value = '09:00';
    if (fromCell && clickedCell) document.getElementById('blk-dia').value = clickedCell.dia;

    poblarSelectSalones('blk-salon');
    poblarProfesoresEnModal('blk-prof');
    openModal('modal-bloque');
}

function editBlock(idx) {
    const b = (schedData[currentGrupo()] || [])[idx];
    if (!b) return;
    editingBlock = idx;
    document.getElementById('bloque-modal-title').textContent = 'Editar bloque';
    document.getElementById('blk-nombre').value = b.nombre;
    document.getElementById('blk-dia').value = b.dia;
    document.getElementById('blk-inicio').value = b.hora_inicio;
    document.getElementById('blk-fin').value = b.hora_fin;
    selectedColor = b.color || 'c0';
    document.querySelectorAll('.cp').forEach(c => c.classList.toggle('sel', c.dataset.c === selectedColor));

    poblarSelectSalones('blk-salon', b.salon);
    poblarProfesoresEnModal('blk-prof', b.profesor);
    openModal('modal-bloque');
}

async function addBlock() {
    const nombre = document.getElementById('blk-nombre').value.trim();
    const dia = document.getElementById('blk-dia').value;
    const salon = document.getElementById('blk-salon').value || 'Sin salón';
    const inicio = document.getElementById('blk-inicio').value;
    const fin = document.getElementById('blk-fin').value;
    const profesor = document.getElementById('blk-prof').value;

    if (!nombre) { alert('Por favor escribe el nombre de la materia.'); return; }
    if (!inicio || !fin || fin <= inicio) { alert('Las horas son inválidas.'); return; }

    const grpId = currentGrupo();
    const bloque = { grupo_id: grpId, nombre, dia, salon, hora_inicio: inicio, hora_fin: fin, profesor, color: selectedColor };

    try {
        if (editingBlock !== null) {
            // Editar: PUT con el id del bloque existente
            const existing = schedData[grpId][editingBlock];
            await apiFetch('api/v1/admin/horarios.php', {
                method: 'PUT',
                body: JSON.stringify({ ...bloque, id: existing.id })
            });
        } else {
            // Crear: POST
            await apiFetch('api/v1/admin/horarios.php', {
                method: 'POST',
                body: JSON.stringify(bloque)
            });
        }
        closeModal('modal-bloque');
        cargarBloques(grpId);   // recargar desde BD
        cargarEstadisticas();
    } catch (e) {
        alert('Error al guardar el bloque.');
    }
}

async function deleteBlock(e, idx) {
    e.stopPropagation();
    const grpId = currentGrupo();
    const b = (schedData[grpId] || [])[idx];
    if (!b) return;
    if (!confirm('¿Eliminar este bloque?')) return;
    try {
        await apiFetch('api/v1/admin/horarios.php', {
            method: 'DELETE',
            body: JSON.stringify({ id: b.id })
        });
        cargarBloques(grpId);
    } catch (e) {
        alert('Error al eliminar el bloque.');
    }
}

async function clearSched() {
    if (!confirm('¿Limpiar todo el horario de este grupo?')) return;
    const grpId = currentGrupo();
    try {
        await apiFetch('api/v1/admin/horarios.php', {
            method: 'DELETE',
            body: JSON.stringify({ grupo_id: grpId, clear_all: true })
        });
        schedData[grpId] = [];
        renderSched();
    } catch (e) {
        alert('Error al limpiar el horario.');
    }
}

/* ══════════════════════════════════════════════════════════════
   NOTIFICACIONES
   ══════════════════════════════════════════════════════════════ */
function markAllRead() {
    document.querySelectorAll('.notif-dot').forEach(d => d.classList.add('read'));
    const badge = document.getElementById('notif-badge');
    if (badge) badge.textContent = '0';
}

async function enviarNotificacionGlobal() {
    const asunto = document.getElementById('notif-asunto').value.trim();
    const tipo = document.getElementById('notif-tipo-global').value;
    const mensaje = document.getElementById('notif-mensaje-global').value.trim();

    if (!asunto) { alert('Por favor escribe un asunto.'); return; }
    if (!mensaje) { alert('Por favor escribe el mensaje.'); return; }

    try {
        // CSRF se inyecta automáticamente en apiFetch para POST
        const data = await apiFetch('api/v1/admin/notificaciones.php', {
            method: 'POST',
            body: JSON.stringify({ asunto, tipo, mensaje, destino: 'todos' })
        });
        if (data.success) {
            closeModal('modal-notif');
            document.getElementById('notif-asunto').value = '';
            document.getElementById('notif-mensaje-global').value = '';

            const lista = document.getElementById('notif-list');
            const item = document.createElement('div');
            item.className = 'notif-item';
            // esc() en asunto, mensaje y tipo
            item.innerHTML = `
                <span class="notif-dot"></span>
                <div class="notif-body">
                    <div class="notif-msg">
                        <strong>${esc(asunto)}</strong> — ${esc(mensaje.substring(0, 80))}${mensaje.length > 80 ? '…' : ''}
                    </div>
                    <div class="notif-time">Tipo: ${esc(tipo)} · Enviado ahora · Para todos los usuarios</div>
                </div>`;
            if (lista.querySelector('p')) lista.innerHTML = '';
            lista.prepend(item);
        } else {
            alert(data.message || 'No se pudo enviar la notificación.');
        }
    } catch (e) {
        console.error('Error al enviar notificación:', e);
        alert('Error de conexión con el servidor.');
    }
}

/* ══════════════════════════════════════════════════════════════
   UTILIDADES DE FORMULARIOS
   ══════════════════════════════════════════════════════════════ */

/** Pobla un <select> con los salones actuales en memoria */
function poblarSelectSalones(selectId, selected = '') {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    sel.innerHTML = salonesData.length
        ? salonesData.map(s => `<option value="${esc(s.nombre)}" ${s.nombre === selected ? 'selected' : ''}>${esc(s.nombre)} (cap. ${esc(s.capacidad)})</option>`).join('')
        : '<option value="">— Sin salones —</option>';
}

/** Pobla un <select> con profesores extraídos de la tabla de usuarios */
function poblarProfesoresEnModal(selectId, selected = '') {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    const profs = [];
    document.querySelectorAll('#usr-tbody tr').forEach(tr => {
        if (tr.querySelector('.badge-prof')) {
            const name = tr.cells[0]?.textContent?.trim();
            if (name) profs.push(name);
        }
    });
    sel.innerHTML = '<option value="Sin asignar">Sin asignar</option>' +
        profs.map(p => `<option value="${esc(p)}" ${p === selected ? 'selected' : ''}>${esc(p)}</option>`).join('');
}

function poblarProfesoresGrupo() { poblarProfesoresEnModal('grp-prof'); }

/* ── MODALS ── */
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) {
    document.getElementById(id)?.classList.remove('open');
    clickedCell = null;
}

// Poblar selects al abrir ciertos modales
const _openModal = openModal;
openModal = function (id) {
    if (id === 'modal-grupo') poblarProfesoresGrupo();
    _openModal(id);
};

// Cerrar modal al hacer clic en el overlay
document.querySelectorAll('.overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

/* ── COLOR ── */
function selectColor(el) {
    document.querySelectorAll('.cp').forEach(c => c.classList.remove('sel'));
    el.classList.add('sel');
    selectedColor = el.dataset.c;
}

/* ── FILTRO DE TABLA ── */
function filterTable(tbodyId, inputId) {
    const q = document.getElementById(inputId).value.toLowerCase();
    document.querySelectorAll('#' + tbodyId + ' tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

/* ══════════════════════════════════════════════════════════════
   INICIALIZACIÓN
   ══════════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    cargarEstadisticas();
    cargarActividad();
    cargarUsuarios();
    cargarGrupos();     // reemplaza el array local de grupos
    cargarSalones();    // reemplaza el array local de salones
    cargarSolicitudes();
});

// SOLICITUDES, APROBAR, RECHAZAR Y DAR MOTIVOS

// ===================== RESPONDER SOLICITUDES CON MOTIVO =====================
let solicitudActualId = null;

function responderSolicitud(id, decision) {
    solicitudActualId = id;

    const titulo = document.getElementById('modal-responder-titulo');
    const sub = document.getElementById('modal-responder-sub');
    const btnAprobar = document.getElementById('btn-aprobar-final');
    const btnRechazar = document.getElementById('btn-rechazar-final');

    // Obtener nombre del usuario desde la tabla
    const fila = document.querySelector(`#sol-tbody tr[data-id="${id}"]`);
    const nombreUsuario = fila ? fila.cells[0].textContent.trim() : 'Usuario';

    sub.innerHTML = `Usuario: <strong>${esc(nombreUsuario)}</strong>`;

    if (decision === 'aprobado') {
        titulo.textContent = "Aprobar Solicitud";
        btnAprobar.style.display = 'inline-flex';
        btnRechazar.style.display = 'none';
    } else {
        titulo.textContent = "Rechazar Solicitud";
        btnAprobar.style.display = 'none';
        btnRechazar.style.display = 'inline-flex';
    }

    document.getElementById('motivo-respuesta').value = '';
    openModal('modal-responder-solicitud');
}

async function confirmarRespuesta(decision) {
    if (!solicitudActualId) return;

    const motivo = document.getElementById('motivo-respuesta').value.trim();

    try {
        const data = await apiFetch('api/v1/admin/solicitudes.php', {
            method: 'POST',
            body: JSON.stringify({
                id: solicitudActualId,
                decision: decision,
                motivo: motivo
            })
        });

        if (data.ok) {
            closeModal('modal-responder-solicitud');
            alert(decision === 'aprobado'
                ? '✅ Solicitud aprobada correctamente'
                : '✅ Solicitud rechazada correctamente');

            cargarSolicitudes();
            cargarEstadisticas();
        } else {
            alert(data.mensaje || 'Error al procesar la solicitud');
        }
    } catch (e) {
        console.error(e);
        alert('Error de conexión con el servidor');
    }
}

// Sobrescribir función para que funcione con los botones de la tabla
window.responderSolicitud = responderSolicitud;