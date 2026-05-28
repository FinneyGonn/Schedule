<?php
define('PAGINA_HTML', true);
require_once 'config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['rol_id'] != 1) {
    header('Location: index.html');
    exit();
}

$admin_nombre   = htmlspecialchars($_SESSION['nombre']   ?? 'Administrador', ENT_QUOTES, 'UTF-8');
$admin_apellido = htmlspecialchars($_SESSION['apellido'] ?? '',               ENT_QUOTES, 'UTF-8');
$admin_nombre_completo = trim($admin_nombre . ' ' . $admin_apellido);

$partes    = array_filter(explode(' ', $admin_nombre_completo));
$iniciales = '';
foreach ($partes as $p) {
    $iniciales .= mb_strtoupper(mb_substr($p, 0, 1));
    if (mb_strlen($iniciales) >= 2) break;
}
if (empty($iniciales)) $iniciales = 'AD';
$iniciales  = htmlspecialchars($iniciales, ENT_QUOTES, 'UTF-8');
$csrf_token = htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Horarios — Admin Dashboard</title>
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="assets/css/admin.css" />
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sb">
        <div class="sb-brand">
            <div class="sb-logo">H</div>
            <span class="sb-name">Horarios</span>
        </div>
        <nav class="sb-nav">
            <span class="sb-label">General</span>
            <button class="ni active" data-panel="home"><span class="ni-ic">▦</span> Resumen</button>
            <button class="ni" data-panel="horario"><span class="ni-ic">▤</span> Horarios</button>
            <span class="sb-label">Administrar</span>
            <button class="ni" data-panel="usuarios"><span class="ni-ic">◻</span> Usuarios</button>
            <button class="ni" data-panel="grupos"><span class="ni-ic">◻</span> Grupos</button>
            <button class="ni" data-panel="salones"><span class="ni-ic">◻</span> Salones</button>
            <span class="sb-label">Sistema</span>
            <button class="ni" data-panel="solicitudes"><span class="ni-ic">◻</span> Solicitudes<span class="nb" id="sol-badge">0</span></button>
            <button class="ni" data-panel="notificaciones"><span class="ni-ic">◻</span> Notificaciones<span class="nb" id="notif-badge">0</span></button>
        </nav>
        <div class="sb-foot">
            <div class="user-row">
                <div class="av"><?= $iniciales ?></div>
                <div class="u-info">
                    <div class="u-name"><?= $admin_nombre_completo ?></div>
                    <div class="u-role">Admin</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">
        <div class="topbar">
            <div class="tb-left">
                <h1 id="tb-title">Resumen</h1>
                <p id="tb-sub">Panel de administración</p>
            </div>
            <div class="tb-right" id="tb-actions"></div>
        </div>

        <div class="content">

            <!-- HOME -->
            <div class="panel active" id="panel-home">
                <div class="stats-grid">
                    <div class="stat-card"><span class="label">Usuarios</span><span class="value" id="count-usuarios">—</span><span class="sub">Registrados</span></div>
                    <div class="stat-card"><span class="label">Profesores</span><span class="value" id="count-profesores">—</span><span class="sub">Activos</span></div>
                    <div class="stat-card"><span class="label">Grupos</span><span class="value" id="count-grupos">—</span><span class="sub">Creados</span></div>
                    <div class="stat-card"><span class="label">Salones</span><span class="value" id="count-salones">—</span><span class="sub">Registrados</span></div>
                    <div class="stat-card accent"><span class="label">Horarios activos</span><span class="value" id="count-horarios">—</span><span class="sub">Esta semana</span></div>
                    <div class="stat-card"><span class="label">Solicitudes</span><span class="value" id="count-solicitudes">—</span><span class="sub">Pendientes</span></div>
                </div>
                <div class="table-wrap">
                    <div class="table-head"><h3>Actividad reciente</h3></div>
                    <div class="notif-list activity-list" style="padding:6px 0">
                        <p style="color:var(--muted);padding:20px 16px;font-size:13px">Cargando actividad…</p>
                    </div>
                </div>
            </div>

            <!-- HORARIO -->
            <div class="panel" id="panel-horario">
                <div id="sched-no-groups" style="display:none">
                    <div class="empty-state">
                        <div class="icon">▤</div>
                        <h3>Sin grupos creados</h3>
                        <p>Primero crea un grupo en la sección <strong>Grupos</strong> para poder gestionar su horario.</p>
                        <button class="btn btn-p" style="margin-top:8px" data-goto="grupos">Ir a Grupos</button>
                    </div>
                </div>
                <div id="sched-content">
                    <div class="sched-toolbar">
                        <select id="sched-grupo" onchange="renderSched()"></select>
                        <button class="btn btn-o" onclick="clearSched()">Limpiar horario</button>
                        <button class="btn btn-p" onclick="abrirModalBloque()">+ Agregar bloque</button>
                    </div>
                    <div class="sched-wrap">
                        <table class="sched-table">
                            <thead id="sched-head"></thead>
                            <tbody id="sched-body"></tbody>
                        </table>
                    </div>
                    <p style="font-size:12px;color:var(--muted);margin-top:10px">Haz clic en cualquier celda vacía para agregar un bloque.</p>
                </div>
            </div>

            <!-- USUARIOS -->
            <div class="panel" id="panel-usuarios">
                <div class="sec-row">
                    <h2>Usuarios</h2>
                    <div style="display:flex;gap:8px">
                        <div class="search-box"><span>⌕</span><input id="usr-search" placeholder="Buscar usuario…" oninput="filterTable('usr-tbody','usr-search')" /></div>
                        <button class="btn btn-p" onclick="openModal('modal-usuario')">+ Nuevo usuario</button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th><th>Nickname</th><th>Correo</th>
                                <th>Rol</th><th>Estado</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="usr-tbody">
                            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px">No hay usuarios registrados aún.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- GRUPOS -->
            <div class="panel" id="panel-grupos">
                <div class="sec-row">
                    <h2>Grupos</h2>
                    <button class="btn btn-p" onclick="openModal('modal-grupo')">+ Nuevo grupo</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre / Salón</th><th>Descripción</th>
                                <th>Miembros</th><th>Profesor asignado</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="grp-tbody">
                            <tr id="grp-empty-row">
                                <td colspan="5" style="text-align:center;color:var(--muted);padding:30px">No hay grupos creados. Usa <strong>+ Nuevo grupo</strong> para comenzar.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SALONES -->
            <div class="panel" id="panel-salones">
                <div class="sec-row">
                    <h2>Salones</h2>
                    <button class="btn btn-p" onclick="openModal('modal-salon')">+ Nuevo salón</button>
                </div>
                <h3 style="font-size:13px;color:var(--muted);margin-bottom:14px;font-weight:400">Disponibilidad actual</h3>
                <div class="avail-grid" id="avail-grid">
                    <p style="color:var(--muted);font-size:13px;grid-column:1/-1">No hay salones registrados aún.</p>
                </div>
                <div style="margin-top:22px">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Nombre</th><th>Capacidad</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody id="sal-tbody">
                                <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:30px">No hay salones registrados aún.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SOLICITUDES -->
            <div class="panel" id="panel-solicitudes">
                <h2 class="sec-title">Solicitudes de Rol</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th><th>Rol actual</th><th>Rol solicitado</th>
                                <th>Fecha</th><th>Estado</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="sol-tbody">
                            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:30px">No hay solicitudes pendientes.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- NOTIFICACIONES -->
            <div class="panel" id="panel-notificaciones">
                <div class="sec-row">
                    <h2>Notificaciones</h2>
                    <div style="display:flex;gap:8px">
                        <button class="btn btn-o" onclick="markAllRead()">Marcar todas leídas</button>
                        <button class="btn btn-p" onclick="openModal('modal-notif')">+ Nueva notificación</button>
                    </div>
                </div>
                <div class="table-wrap">
                    <div class="notif-list" id="notif-list" style="padding:6px 0">
                        <p style="color:var(--muted);padding:20px 16px;font-size:13px">No hay notificaciones enviadas.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- ===================== MODALS ===================== -->

    <!-- Modal: Responder solicitud (aprobar / rechazar) -->
    <div class="overlay" id="modal-responder-solicitud">
        <div class="modal" style="max-width:520px">
            <h2 id="modal-responder-titulo">Responder Solicitud</h2>
            <p class="sub" id="modal-responder-sub">Usuario: <strong id="solicitud-usuario"></strong></p>
            <div class="field">
                <label>Motivo <span style="font-weight:400;color:var(--muted)">(opcional)</span></label>
                <textarea id="motivo-respuesta" rows="4" placeholder="Explica brevemente el motivo de tu decisión..."></textarea>
            </div>
            <div class="modal-foot">
                <button class="btn btn-o" onclick="closeModal('modal-responder-solicitud')">Cancelar</button>
                <button class="btn btn-s" id="btn-aprobar-final" onclick="confirmarRespuesta('aprobado')">✅ Aprobar</button>
                <button class="btn btn-d" id="btn-rechazar-final" onclick="confirmarRespuesta('rechazado')">❌ Rechazar</button>
            </div>
        </div>
    </div>

    <!-- Modal: Revocar aprobación -->
    <div class="overlay" id="modal-revocar-solicitud">
        <div class="modal" style="max-width:520px">
            <h2>Revocar aprobación</h2>
            <p class="sub">El rol del usuario será devuelto al que tenía antes de esta solicitud.</p>
            <div class="field">
                <label>Motivo de la revocación <span style="font-weight:400;color:var(--muted)">(opcional)</span></label>
                <textarea id="motivo-revocar" rows="4" placeholder="Ej: Se aprobó por error, el usuario no cumple los requisitos..."></textarea>
            </div>
            <div style="background:var(--surface2);border:1px solid var(--border);border-radius:9px;padding:12px 14px;margin-bottom:4px;display:flex;align-items:center;gap:10px">
                <span style="font-size:18px">⚠️</span>
                <span style="font-size:12px;color:var(--muted);line-height:1.5">Esta acción <strong style="color:var(--text)">cambiará el rol del usuario</strong> de vuelta al que tenía antes.</span>
            </div>
            <div class="modal-foot">
                <button class="btn btn-o" onclick="closeModal('modal-revocar-solicitud')">Cancelar</button>
                <button class="btn btn-d" onclick="confirmarRevocacion()">🔄 Confirmar revocación</button>
            </div>
        </div>
    </div>

    <!-- Modal: Bloque de horario -->
    <div class="overlay" id="modal-bloque">
        <div class="modal">
            <h2 id="bloque-modal-title">Agregar bloque</h2>
            <p class="sub">Completa los datos del bloque de clase</p>
            <div class="field">
                <label>Materia / Nombre</label>
                <input id="blk-nombre" placeholder="Ej: Matemáticas" />
            </div>
            <div class="field-row">
                <div class="field">
                    <label>Día</label>
                    <select id="blk-dia">
                        <option value="lunes">Lunes</option>
                        <option value="martes">Martes</option>
                        <option value="miercoles">Miércoles</option>
                        <option value="jueves">Jueves</option>
                        <option value="viernes">Viernes</option>
                        <option value="sabado">Sábado</option>
                    </select>
                </div>
                <div class="field">
                    <label>Salón</label>
                    <select id="blk-salon"><option value="">— Sin salones —</option></select>
                </div>
            </div>
            <div class="field-row">
                <div class="field"><label>Hora inicio</label><input type="time" id="blk-inicio" value="07:00" /></div>
                <div class="field"><label>Hora fin</label><input type="time" id="blk-fin" value="09:00" /></div>
            </div>
            <div class="field">
                <label>Profesor</label>
                <select id="blk-prof"><option value="Sin asignar">Sin asignar</option></select>
            </div>
            <div class="field">
                <label>Color del bloque</label>
                <div class="color-pick" id="color-pick">
                    <div class="cp sel" style="background:var(--c0)" data-c="c0" onclick="selectColor(this)"></div>
                    <div class="cp" style="background:var(--c1)" data-c="c1" onclick="selectColor(this)"></div>
                    <div class="cp" style="background:var(--c2)" data-c="c2" onclick="selectColor(this)"></div>
                    <div class="cp" style="background:var(--c3)" data-c="c3" onclick="selectColor(this)"></div>
                    <div class="cp" style="background:var(--c4)" data-c="c4" onclick="selectColor(this)"></div>
                    <div class="cp" style="background:var(--c5)" data-c="c5" onclick="selectColor(this)"></div>
                    <div class="cp" style="background:var(--c6)" data-c="c6" onclick="selectColor(this)"></div>
                    <div class="cp" style="background:var(--c7)" data-c="c7" onclick="selectColor(this)"></div>
                </div>
            </div>
            <div class="modal-foot">
                <button class="btn btn-o" onclick="closeModal('modal-bloque')">Cancelar</button>
                <button class="btn btn-p" onclick="addBlock()">Guardar bloque</button>
            </div>
        </div>
    </div>

    <!-- Modal: Usuario -->
    <div class="overlay" id="modal-usuario">
        <div class="modal">
            <h2>Nuevo usuario</h2>
            <p class="sub">Completa los datos del usuario</p>
            <div class="field-row">
                <div class="field"><label>Nombre</label><input id="usr-nombre" placeholder="Juan" /></div>
                <div class="field"><label>Apellido</label><input id="usr-apellido" placeholder="Pérez" /></div>
            </div>
            <div class="field"><label>Nickname</label><input id="usr-nick" placeholder="juan_perez" /></div>
            <div class="field"><label>Correo</label><input type="email" id="usr-correo" placeholder="juan@correo.co" /></div>
            <div class="field">
                <label>Rol</label>
                <select id="usr-rol">
                    <option value="3">Estudiante</option>
                    <option value="2">Profesor</option>
                    <option value="1">Administrador</option>
                </select>
            </div>
            <div class="field"><label>Contraseña</label><input type="password" id="usr-pass" placeholder="••••••••" /></div>
            <div class="modal-foot">
                <button class="btn btn-o" onclick="closeModal('modal-usuario')">Cancelar</button>
                <button class="btn btn-p" onclick="saveUsuario()">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Modal: Grupo -->
    <div class="overlay" id="modal-grupo">
        <div class="modal">
            <h2>Nuevo grupo</h2>
            <p class="sub">Define el nombre del grupo o salón de tu institución</p>
            <div class="field">
                <label>Nombre del grupo / Salón</label>
                <input id="grp-nombre" placeholder="Ej: 11-B, 10-2, Grado 9A…" />
                <span class="field-hint">Usa el formato que maneja tu institución.</span>
            </div>
            <div class="field">
                <label>Descripción</label>
                <textarea id="grp-desc" rows="3" placeholder="Descripción del grupo…"></textarea>
            </div>
            <div class="field">
                <label>Profesor asignado</label>
                <select id="grp-prof"><option value="">Sin asignar</option></select>
            </div>
            <div class="modal-foot">
                <button class="btn btn-o" onclick="closeModal('modal-grupo')">Cancelar</button>
                <button class="btn btn-p" onclick="saveGrupo()">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Modal: Salón -->
    <div class="overlay" id="modal-salon">
        <div class="modal">
            <h2>Nuevo salón</h2>
            <p class="sub">Registra el salón y su capacidad</p>
            <div class="field"><label>Nombre del salón</label><input id="sal-nombre" placeholder="Ej: Lab 104, Aula 3…" /></div>
            <div class="field"><label>Capacidad</label><input type="number" id="sal-cap" placeholder="30" min="1" /></div>
            <div class="modal-foot">
                <button class="btn btn-o" onclick="closeModal('modal-salon')">Cancelar</button>
                <button class="btn btn-p" onclick="saveSalon()">Guardar</button>
            </div>
        </div>
    </div>

    <!-- Modal: Nueva Notificación -->
    <div class="overlay" id="modal-notif">
        <div class="modal" style="max-width:540px">
            <h2>Nueva notificación</h2>
            <p class="sub">El mensaje se enviará a todos los usuarios registrados</p>
            <div class="field">
                <label>Asunto</label>
                <input id="notif-asunto" placeholder="Ej: Cambios en el horario de la semana…" />
            </div>
            <div class="field">
                <label>Tipo</label>
                <select id="notif-tipo-global">
                    <option value="Sistema">Sistema</option>
                    <option value="Urgente">Urgente</option>
                    <option value="Aviso">Aviso General</option>
                </select>
            </div>
            <div class="field">
                <label>Mensaje</label>
                <textarea id="notif-mensaje-global" rows="5" placeholder="Escribe aquí el contenido del mensaje…" style="resize:vertical"></textarea>
            </div>
            <div style="background:var(--surface2);border:1px solid var(--border);border-radius:9px;padding:12px 14px;margin-bottom:4px;display:flex;align-items:center;gap:10px">
                <span style="font-size:18px">📢</span>
                <span style="font-size:12px;color:var(--muted);line-height:1.5">Esta notificación será visible para <strong style="color:var(--text)">todos los usuarios</strong> la próxima vez que inicien sesión.</span>
            </div>
            <div class="modal-foot">
                <button class="btn btn-o" onclick="closeModal('modal-notif')">Cancelar</button>
                <button class="btn btn-p" onclick="enviarNotificacionGlobal()">📨 Enviar a todos</button>
            </div>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>