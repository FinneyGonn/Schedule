const COLORS = { c0: '#c8a96e', c1: '#64a0dc', c2: '#78c88c', c3: '#c878b4', c4: '#dc9664', c5: '#a08cdc', c6: '#60c8c0', c7: '#e0b060' };

// Estado de la app
const grupos = [];
const salonesData = [];
const schedData = {};

let selectedColor = 'c0';
let editingBlock = null;
let clickedCell = null;

/* NAVEGACIÓN */
const panelMeta = {
    home: { title: 'Resumen', sub: 'Panel de administración' },
    horario: { title: 'Gestión de Horarios', sub: 'Crea y edita bloques por grupo' },
    usuarios: { title: 'Usuarios', sub: 'Gestiona cuentas y roles' },
    grupos: { title: 'Grupos', sub: 'Organiza grupos y asignaciones' },
    salones: { title: 'Salones', sub: 'Administra aulas y disponibilidad' },
    solicitudes: { title: 'Solicitudes de Rol', sub: 'Aprueba o rechaza cambios de rol' },
    notificaciones: { title: 'Notificaciones', sub: 'Mensajes del sistema' },
};

function go(name, btn) {
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.ni').forEach(n => n.classList.remove('active'));
    document.getElementById('panel-' + name).classList.add('active');
    if (btn) btn.classList.add('active');
    const m = panelMeta[name] || { title: name, sub: '' };
    document.getElementById('tb-title').textContent = m.title;
    document.getElementById('tb-sub').textContent = m.sub;
    if (name === 'notificaciones') { const b = document.getElementById('notif-badge'); if (b) b.remove(); }
    if (name === 'horario') renderSchedPanel();
}

/* --- NUEVA FUNCIÓN: CARGAR USUARIOS DESDE API --- */
const cargarUsuarios = async () => {
    const tbody = document.getElementById('usr-tbody');
    if (!tbody) return;

    try {
        // Usamos ruta relativa para evitar el error 404 de localhost
        const resp = await fetch('api/v1/admin/usuarios.php');
        if (!resp.ok) throw new Error('No se pudo obtener la lista de usuarios');

        const data = await resp.json();

        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:var(--muted)">No hay usuarios registrados.</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(u => {
            // Definir clase de color según rol_id (1:Admin, 2:Prof, 3:Est)
            const badgeClass = u.rol_id == 1 ? 'badge-admin' : (u.rol_id == 2 ? 'badge-prof' : 'badge-est');

            return `
                    <tr>
                        <td>${u.nombre} ${u.apellido}</td>
                        <td>${u.nickname || u.usuario}</td>
                        <td>${u.correo}</td>
                        <td><span class="badge ${badgeClass}">${u.rol}</span></td>
                        <td><span class="badge badge-act">Activo</span></td>
                        <td>
                            <div class="actions-cell">
                                <button class="btn btn-o btn-sm" onclick="editUser(${u.id})">Editar</button>
                                <button class="btn btn-d btn-sm">Desactivar</button>
                            </div>
                        </td>
                    </tr>`;
        }).join('');
    } catch (e) {
        console.error("Error al cargar usuarios:", e);
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--danger)">Error al conectar con el servidor.</td></tr>';
    }
};

/* PANEL HORARIO */
function renderSchedPanel() {
    const noGroups = document.getElementById('sched-no-groups');
    const schedCont = document.getElementById('sched-content');
    if (grupos.length === 0) {
        if (noGroups) noGroups.style.display = 'block';
        if (schedCont) schedCont.style.display = 'none';
        return;
    }
    if (noGroups) noGroups.style.display = 'none';
    if (schedCont) schedCont.style.display = 'block';
    const sel = document.getElementById('sched-grupo');
    sel.innerHTML = grupos.map(g => `<option value="${g.id}">${g.nombre}</option>`).join('');
    renderSched();
}

/* HORARIO RENDER */
const DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado'];
const DIAS_LABEL = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

function currentGrupo() { return document.getElementById('sched-grupo').value; }

function buildHoras() {
    const arr = [];
    for (let h = 6; h < 20; h++) arr.push(String(h).padStart(2, '0') + ':00');
    return arr;
}

function getBlocks(dia, hora) {
    return (schedData[currentGrupo()] || []).filter(b => b.dia === dia && b.hora_inicio === hora);
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
                    for (let i = 1; i < span; i++) occ[`${dia}-${String(sh + i).padStart(2, '0')}:00`] = true;
                }
                const col = COLORS[b.color] || COLORS.c0;
                const colA = col + '28';
                const idx = (schedData[currentGrupo()] || []).indexOf(b);
                td.innerHTML = `
                        <div class="blk" style="background:${colA};border-left:3px solid ${col};color:${col}" onclick="editBlock(${idx})">
                            <span class="blk-name">${b.nombre}</span>
                            <span class="blk-info">📍 ${b.salon} · ${b.hora_inicio}–${b.hora_fin}</span>
                            <span class="blk-info">${b.profesor}</span>
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

/* MODALS */
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); clickedCell = null; }

document.querySelectorAll('.overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

/* ESTADÍSTICAS Y ACTIVIDAD (BACKEND) */
const cargarEstadisticas = async () => {
    try {
        const resp = await fetch('api/v1/admin/stats.php');
        if (!resp.ok) throw new Error('Error en stats');
        const data = await resp.json();
        const ids = {
            total_usuarios: 'count-usuarios',
            total_profesores: 'count-profesores',
            total_solicitudes: 'count-solicitudes',
            total_salones: 'count-salones',
            total_horarios: 'count-horarios'
        };
        Object.keys(ids).forEach(k => {
            const el = document.getElementById(ids[k]);
            if (el && data[k] !== undefined) el.textContent = data[k];
        });
    } catch (e) { console.error('Error stats:', e); }
};

const cargarActividad = async () => {
    const lista = document.querySelector('.activity-list');
    if (!lista) return;
    try {
        const resp = await fetch('api/v1/admin/actividad.php');
        const data = await resp.json();
        if (!data || data.length === 0) {
            lista.innerHTML = '<p style="color:var(--muted);padding:16px;font-size:13px">No hay actividad reciente.</p>';
            return;
        }
        lista.innerHTML = data.map(item => `
                <div class="notif-item">
                    <span class="notif-dot ${item.estado !== 'pendiente' ? 'read' : ''}"></span>
                    <div class="notif-body">
                        <div class="notif-msg"><strong>${item.nombre}</strong> solicitó cambio de rol a <strong>${item.nombre_rol}</strong></div>
                        <div class="notif-time">Estado: ${item.estado} · ${item.created_at}</div>
                    </div>
                </div>`).join('');
    } catch (e) { console.error('Error actividad:', e); }
};

document.addEventListener('DOMContentLoaded', () => {
    // Ejecutar todas las cargas asíncronas
    cargarEstadisticas();
    cargarActividad();
    cargarUsuarios(); // Carga la lista de usuarios real
    cargarSolicitudes();
});

async function cargarSolicitudes() {
    try {
        const resp = await fetch('api/v1/admin/actividad.php');
        if (!resp.ok) return;
        const data = await resp.json();

        const badge = document.getElementById('sol-badge');
        const tbody = document.getElementById('sol-tbody');
        const pending = data.filter(d => d.estado === 'pendiente');

        if (badge) badge.textContent = pending.length;

        if (!data || data.length === 0) return;

        tbody.innerHTML = data.map(s => `
                <tr>
                    <td>${s.nombre}</td>
                    <td>—</td>
                    <td>${s.nombre_rol}</td>
                    <td>${s.created_at}</td>
                    <td><span class="badge ${s.estado === 'pendiente' ? 'badge-pend' : 'badge-act'}">${s.estado}</span></td>
                    <td>
                        <div class="actions-cell">
                            <button class="btn btn-s btn-sm">Aprobar</button>
                            <button class="btn btn-d btn-sm">Rechazar</button>
                        </div>
                    </td>
                </tr>`).join('');
    } catch (e) { console.error('Error solicitudes:', e); }
}
/* ── FILTRO DE TABLA ── */
function filterTable(tbodyId, inputId) {
    const q = document.getElementById(inputId).value.toLowerCase();
    document.querySelectorAll('#' + tbodyId + ' tr').forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

/* ── COLOR ── */
function selectColor(el) {
    document.querySelectorAll('.cp').forEach(c => c.classList.remove('sel'));
    el.classList.add('sel');
    selectedColor = el.dataset.c;
}

/* ── MODAL BLOQUE ── */
function abrirModalBloque(fromCell = false) {
    editingBlock = null;
    document.getElementById('bloque-modal-title').textContent = 'Agregar bloque';
    document.getElementById('blk-nombre').value = '';
    document.getElementById('blk-inicio').value = fromCell && clickedCell ? clickedCell.hora : '07:00';
    document.getElementById('blk-fin').value = '09:00';
    if (fromCell && clickedCell) {
        document.getElementById('blk-dia').value = clickedCell.dia;
    }
    // Poblar select de salones
    const selSalon = document.getElementById('blk-salon');
    selSalon.innerHTML = salonesData.length
        ? salonesData.map(s => `<option value="${s.nombre}">${s.nombre} (cap. ${s.capacidad})</option>`).join('')
        : '<option value="">— Sin salones —</option>';
    // Poblar select de profesores desde los usuarios cargados
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
    document.querySelectorAll('.cp').forEach(c => {
        c.classList.toggle('sel', c.dataset.c === selectedColor);
    });
    const selSalon = document.getElementById('blk-salon');
    selSalon.innerHTML = salonesData.length
        ? salonesData.map(s => `<option value="${s.nombre}" ${s.nombre === b.salon ? 'selected' : ''}>${s.nombre} (cap. ${s.capacidad})</option>`).join('')
        : `<option value="${b.salon}">${b.salon}</option>`;
    poblarProfesoresEnModal('blk-prof', b.profesor);
    openModal('modal-bloque');
}

function addBlock() {
    const nombre = document.getElementById('blk-nombre').value.trim();
    const dia = document.getElementById('blk-dia').value;
    const salon = document.getElementById('blk-salon').value || 'Sin salón';
    const inicio = document.getElementById('blk-inicio').value;
    const fin = document.getElementById('blk-fin').value;
    const profesor = document.getElementById('blk-prof').value;

    if (!nombre) { alert('Por favor escribe el nombre de la materia.'); return; }
    if (!inicio || !fin || fin <= inicio) { alert('Las horas son inválidas.'); return; }

    const grp = currentGrupo();
    if (!schedData[grp]) schedData[grp] = [];

    const bloque = { nombre, dia, salon, hora_inicio: inicio, hora_fin: fin, profesor, color: selectedColor };

    if (editingBlock !== null) {
        schedData[grp][editingBlock] = bloque;
    } else {
        schedData[grp].push(bloque);
    }

    closeModal('modal-bloque');
    renderSched();
}

function deleteBlock(e, idx) {
    e.stopPropagation();
    const grp = currentGrupo();
    if (!schedData[grp]) return;
    schedData[grp].splice(idx, 1);
    renderSched();
}

function clearSched() {
    if (!confirm('¿Limpiar todo el horario de este grupo?')) return;
    schedData[currentGrupo()] = [];
    renderSched();
}

/* ── USUARIOS ── */
function saveUsuario() {
    const nombre = document.getElementById('usr-nombre').value.trim();
    const apellido = document.getElementById('usr-apellido').value.trim();
    const nick = document.getElementById('usr-nick').value.trim();
    const correo = document.getElementById('usr-correo').value.trim();
    const rol = document.getElementById('usr-rol').value;
    const pass = document.getElementById('usr-pass').value;

    if (!nombre || !correo || !pass) { alert('Nombre, correo y contraseña son obligatorios.'); return; }

    fetch('api/v1/admin/usuarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre, apellido, nickname: nick, correo, rol_id: rol, password: pass })
    })
        .then(r => r.json())
        .then(data => {
            if (data.ok !== false) {
                closeModal('modal-usuario');
                cargarUsuarios();
            } else {
                alert(data.mensaje || 'Error al guardar el usuario.');
            }
        })
        .catch(() => alert('Error de conexión al guardar usuario.'));
}

function editUser(id) {
    alert('Función de edición próximamente (ID: ' + id + ')');
}

/* ── GRUPOS ── */
function saveGrupo() {
    const nombre = document.getElementById('grp-nombre').value.trim();
    const desc = document.getElementById('grp-desc').value.trim();
    const profId = document.getElementById('grp-prof').value;
    const profNombre = document.getElementById('grp-prof').options[document.getElementById('grp-prof').selectedIndex]?.text || '';

    if (!nombre) { alert('El nombre del grupo es obligatorio.'); return; }

    const id = Date.now();
    grupos.push({ id, nombre, desc, profId, profNombre });

    const tbody = document.getElementById('grp-tbody');
    const emptyRow = document.getElementById('grp-empty-row');
    if (emptyRow) emptyRow.remove();

    const tr = document.createElement('tr');
    tr.dataset.gid = id;
    tr.innerHTML = `
            <td><strong>${nombre}</strong></td>
            <td>${desc || '—'}</td>
            <td>0</td>
            <td>${profNombre !== 'Sin asignar' && profNombre !== '' ? profNombre : '—'}</td>
            <td>
                <div class="actions-cell">
                    <button class="btn btn-o btn-sm" onclick="go('horario', document.querySelectorAll('.ni')[1])">Ver horario</button>
                    <button class="btn btn-d btn-sm" onclick="eliminarGrupo(${id}, this)">Eliminar</button>
                </div>
            </td>`;
    tbody.appendChild(tr);

    closeModal('modal-grupo');
    document.getElementById('grp-nombre').value = '';
    document.getElementById('grp-desc').value = '';

    // Actualizar el select de horarios
    const sel = document.getElementById('sched-grupo');
    if (sel) {
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = nombre;
        sel.appendChild(opt);
    }

    // Actualizar stat card
    const el = document.getElementById('count-grupos');
    if (el && el.textContent !== '—') el.textContent = parseInt(el.textContent || 0) + 1;
}

function eliminarGrupo(id, btn) {
    if (!confirm('¿Eliminar este grupo?')) return;
    const idx = grupos.findIndex(g => g.id === id);
    if (idx > -1) grupos.splice(idx, 1);
    btn.closest('tr').remove();
    delete schedData[id];
    if (document.getElementById('grp-tbody').rows.length === 0) {
        document.getElementById('grp-tbody').innerHTML = '<tr id="grp-empty-row"><td colspan="5" style="text-align:center;color:var(--muted);padding:30px">No hay grupos creados.</td></tr>';
    }
}

/* ── SALONES ── */
function saveSalon() {
    const nombre = document.getElementById('sal-nombre').value.trim();
    const cap = parseInt(document.getElementById('sal-cap').value) || 0;

    if (!nombre) { alert('El nombre del salón es obligatorio.'); return; }

    salonesData.push({ nombre, capacidad: cap });

    // Tabla
    const tbody = document.getElementById('sal-tbody');
    if (tbody.querySelector('td[colspan]')) tbody.innerHTML = '';
    const tr = document.createElement('tr');
    tr.innerHTML = `
            <td>${nombre}</td>
            <td>${cap}</td>
            <td><span class="badge badge-act">Disponible</span></td>
            <td>
                <div class="actions-cell">
                    <button class="btn btn-d btn-sm" onclick="eliminarSalon('${nombre}', this)">Eliminar</button>
                </div>
            </td>`;
    tbody.appendChild(tr);

    // Tarjeta disponibilidad
    const grid = document.getElementById('avail-grid');
    if (grid.querySelector('p')) grid.innerHTML = '';
    const card = document.createElement('div');
    card.className = 'avail-card';
    card.dataset.salon = nombre;
    card.innerHTML = `
            <h4>${nombre}</h4>
            <div class="cap">Capacidad: ${cap}</div>
            <div class="avail-bar-wrap"><div class="avail-bar" style="width:0%"></div></div>`;
    grid.appendChild(card);

    closeModal('modal-salon');
    document.getElementById('sal-nombre').value = '';
    document.getElementById('sal-cap').value = '';

    const el = document.getElementById('count-salones');
    if (el && el.textContent !== '—') el.textContent = parseInt(el.textContent || 0) + 1;
}

function eliminarSalon(nombre, btn) {
    if (!confirm('¿Eliminar el salón "' + nombre + '"?')) return;
    const idx = salonesData.findIndex(s => s.nombre === nombre);
    if (idx > -1) salonesData.splice(idx, 1);
    btn.closest('tr').remove();
    const card = document.querySelector(`.avail-card[data-salon="${nombre}"]`);
    if (card) card.remove();
    if (document.getElementById('sal-tbody').rows.length === 0) {
        document.getElementById('sal-tbody').innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:30px">No hay salones registrados aún.</td></tr>';
    }
}

/* ── NOTIFICACIONES ── */
function markAllRead() {
    document.querySelectorAll('.notif-dot').forEach(d => d.classList.add('read'));
    const badge = document.getElementById('notif-badge');
    if (badge) badge.textContent = '0';
}

/* ── UTILIDADES ── */
function poblarProfesoresEnModal(selectId, selected = '') {
    const sel = document.getElementById(selectId);
    // Obtener profesores desde la tabla de usuarios
    const rows = document.querySelectorAll('#usr-tbody tr');
    const profs = [];
    rows.forEach(tr => {
        const badgeEl = tr.querySelector('.badge-prof');
        if (badgeEl) {
            const name = tr.cells[0]?.textContent?.trim();
            if (name) profs.push(name);
        }
    });
    sel.innerHTML = '<option value="Sin asignar">Sin asignar</option>' +
        profs.map(p => `<option value="${p}" ${p === selected ? 'selected' : ''}>${p}</option>`).join('');
}

/* Poblar selector de profesores en modal de Grupo */
function poblarProfesoresGrupo() {
    poblarProfesoresEnModal('grp-prof');
}

// Extender openModal para poblar selects dinámicamente
const _openModal = openModal;
openModal = function (id) {
    if (id === 'modal-grupo') poblarProfesoresGrupo();
    _openModal(id);
};

async function enviarNotificacionGlobal() {
    const asunto = document.getElementById('notif-asunto').value.trim();
    const tipo = document.getElementById('notif-tipo-global').value;
    const mensaje = document.getElementById('notif-mensaje-global').value.trim();

    if (!asunto) { alert('Por favor escribe un asunto.'); return; }
    if (!mensaje) { alert('Por favor escribe el mensaje.'); return; }

    try {
        const resp = await fetch('api/v1/admin/notificaciones.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ asunto, tipo, mensaje, destino: 'todos' })
        });

        if (!resp.ok) throw new Error('Error al enviar');
        const data = await resp.json();

        if (data.success) {
            closeModal('modal-notif');
            document.getElementById('notif-asunto').value = '';
            document.getElementById('notif-mensaje-global').value = '';

            // Mostrar confirmación en la lista
            const lista = document.getElementById('notif-list');
            const item = document.createElement('div');
            item.className = 'notif-item';
            item.innerHTML = `
                <span class="notif-dot"></span>
                <div class="notif-body">
                    <div class="notif-msg"><strong>${asunto}</strong> — ${mensaje.substring(0, 80)}${mensaje.length > 80 ? '…' : ''}</div>
                    <div class="notif-time">Tipo: ${tipo} · Enviado ahora · Para todos los usuarios</div>
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