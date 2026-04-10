<?php
session_start();
require_once 'config/config.php';

// Verificar si el usuario está logueado y es admin (rol_id = 1 según tu tabla)
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol_id'] != 1) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Horarios — Admin Dashboard</title>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        :root {
            --bg: #0d0f14;
            --surface: #13161d;
            --surface2: #1a1e28;
            --surface3: #21263a;
            --border: #2a2f3e;
            --accent: #c8a96e;
            --accent2: #e8c98e;
            --text: #eeeae2;
            --muted: #7a7d8a;
            --danger: #e07070;
            --success: #70c9a0;
            --sw: 230px;
            --c0: #c8a96e;
            --c1: #64a0dc;
            --c2: #78c88c;
            --c3: #c878b4;
            --c4: #dc9664;
            --c5: #a08cdc;
            --c6: #60c8c0;
            --c7: #e0b060
        }

        html,
        body {
            height: 100%;
            overflow: hidden
        }

        body {
            display: flex;
            font-family: 'DM Sans', sans-serif;
            color: var(--text);
            background: var(--bg)
        }

        /* SIDEBAR */
        .sb {
            width: var(--sw);
            min-height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0
        }

        .sb-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 22px 18px 18px;
            border-bottom: 1px solid var(--border)
        }

        .sb-logo {
            width: 32px;
            height: 32px;
            background: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0
        }

        .sb-name {
            font-family: 'DM Serif Display', serif;
            font-size: 17px
        }

        .sb-nav {
            flex: 1;
            padding: 12px 8px;
            display: flex;
            flex-direction: column;
            gap: 1px;
            overflow-y: auto
        }

        .sb-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: .1em;
            text-transform: uppercase;
            padding: 14px 10px 5px
        }

        .ni {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 11px;
            border-radius: 9px;
            cursor: pointer;
            font-size: 13.5px;
            color: var(--muted);
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            font-family: 'DM Sans', sans-serif;
            transition: background .15s, color .15s
        }

        .ni:hover {
            background: var(--surface2);
            color: var(--text)
        }

        .ni.active {
            background: var(--surface3);
            color: var(--accent);
            font-weight: 500
        }

        .ni-ic {
            width: 18px;
            text-align: center;
            flex-shrink: 0;
            font-size: 15px
        }

        .nb {
            margin-left: auto;
            background: var(--accent);
            color: #0d0f14;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 20px
        }

        .sb-foot {
            padding: 10px 8px 18px;
            border-top: 1px solid var(--border)
        }

        .user-row {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 9px 11px;
            border-radius: 9px;
            cursor: pointer;
            transition: background .15s
        }

        .user-row:hover {
            background: var(--surface2)
        }

        .av {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--surface3);
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0
        }

        .u-info {
            flex: 1;
            min-width: 0
        }

        .u-name {
            font-size: 12.5px;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .u-role {
            font-size: 11px;
            color: var(--muted)
        }

        /* MAIN */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            min-width: 0
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 28px;
            border-bottom: 1px solid var(--border);
            background: var(--bg);
            flex-shrink: 0
        }

        .tb-left h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 20px
        }

        .tb-left p {
            font-size: 12px;
            color: var(--muted);
            margin-top: 2px;
            font-weight: 300
        }

        .tb-right {
            display: flex;
            gap: 8px;
            align-items: center
        }

        .btn {
            padding: 8px 14px;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            cursor: pointer;
            transition: all .18s;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500
        }

        .btn-o {
            border: 1px solid var(--border);
            background: transparent;
            color: var(--muted)
        }

        .btn-o:hover {
            border-color: var(--accent);
            color: var(--accent)
        }

        .btn-p {
            border: none;
            background: var(--accent);
            color: #0d0f14;
            font-weight: 600
        }

        .btn-p:hover {
            background: var(--accent2)
        }

        .btn-d {
            border: 1px solid rgba(224, 112, 112, .25);
            background: rgba(224, 112, 112, .15);
            color: var(--danger)
        }

        .btn-d:hover {
            background: rgba(224, 112, 112, .25)
        }

        .btn-s {
            border: 1px solid rgba(112, 201, 160, .25);
            background: rgba(112, 201, 160, .15);
            color: var(--success)
        }

        .btn-s:hover {
            background: rgba(112, 201, 160, .25)
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px
        }

        .content {
            flex: 1;
            overflow-y: auto;
            padding: 24px 28px
        }

        .panel {
            display: none
        }

        .panel.active {
            display: block;
            animation: fadeIn .25s ease
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 28px
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px 16px;
            display: flex;
            flex-direction: column;
            gap: 6px
        }

        .stat-card .label {
            font-size: 11px;
            color: var(--muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .06em
        }

        .stat-card .value {
            font-family: 'DM Serif Display', serif;
            font-size: 32px;
            line-height: 1
        }

        .stat-card .sub {
            font-size: 11px;
            color: var(--muted)
        }

        .stat-card.accent {
            border-color: rgba(200, 169, 110, .3);
            background: rgba(200, 169, 110, .06)
        }

        .stat-card.accent .value {
            color: var(--accent)
        }

        /* TABLES */
        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden
        }

        .table-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border)
        }

        .table-head h3 {
            font-size: 14px;
            font-weight: 600
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        thead th {
            padding: 11px 14px;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .06em;
            text-align: left;
            border-bottom: 1px solid var(--border);
            background: var(--surface)
        }

        tbody td {
            padding: 11px 14px;
            font-size: 13px;
            border-bottom: 1px solid var(--border)
        }

        tbody tr:last-child td {
            border-bottom: none
        }

        tbody tr:hover {
            background: var(--surface2)
        }

        .actions-cell {
            display: flex;
            gap: 6px;
            align-items: center
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600
        }

        .badge-admin {
            background: rgba(200, 169, 110, .15);
            color: var(--accent)
        }

        .badge-prof {
            background: rgba(100, 160, 220, .15);
            color: #64a0dc
        }

        .badge-est {
            background: rgba(120, 200, 140, .15);
            color: #78c88c
        }

        .badge-pend {
            background: rgba(220, 150, 80, .15);
            color: #dc9650
        }

        .badge-act {
            background: rgba(112, 201, 160, .15);
            color: var(--success)
        }

        .badge-inac {
            background: rgba(224, 112, 112, .15);
            color: var(--danger)
        }

        .badge-indu {
            background: rgba(160, 140, 220, .15);
            color: #a08cdc
        }

        /* SCHEDULE */
        .sched-toolbar {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap
        }

        .sched-toolbar select,
        .sched-toolbar input {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 8px 12px;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            outline: none
        }

        .sched-toolbar select:focus,
        .sched-toolbar input:focus {
            border-color: var(--accent)
        }

        .sched-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface)
        }

        .sched-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 720px
        }

        .sched-table th {
            padding: 12px 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .05em;
            text-align: center;
            border-bottom: 1px solid var(--border)
        }

        .sched-table th:first-child {
            text-align: left;
            padding-left: 16px;
            width: 72px
        }

        .time-lbl {
            padding: 0 8px 0 16px;
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
            border-right: 1px solid var(--border);
            white-space: nowrap;
            vertical-align: middle
        }

        .sc {
            padding: 4px 5px;
            vertical-align: top;
            height: 64px;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            position: relative;
            cursor: pointer;
            transition: background .15s
        }

        .sc:last-child {
            border-right: none
        }

        .sc:hover .add-hint {
            opacity: 1
        }

        .add-hint {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: var(--border);
            opacity: 0;
            transition: opacity .15s;
            pointer-events: none
        }

        .blk {
            border-radius: 7px;
            padding: 6px 8px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 2px;
            cursor: pointer;
            transition: filter .15s;
            position: relative;
            z-index: 1
        }

        .blk:hover {
            filter: brightness(1.2)
        }

        .blk-name {
            font-size: 11.5px;
            font-weight: 600;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis
        }

        .blk-info {
            font-size: 10px;
            opacity: .75
        }

        .blk-del {
            position: absolute;
            top: 3px;
            right: 4px;
            background: rgba(0, 0, 0, .4);
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 10px;
            cursor: pointer;
            padding: 1px 4px;
            opacity: 0;
            transition: opacity .15s
        }

        .blk:hover .blk-del {
            opacity: 1
        }

        /* EMPTY STATES */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 80px 20px;
            gap: 14px;
            color: var(--muted);
            text-align: center
        }

        .empty-state .icon {
            font-size: 48px;
            opacity: .3
        }

        .empty-state h3 {
            font-size: 16px;
            color: var(--text);
            font-family: 'DM Serif Display', serif
        }

        .empty-state p {
            font-size: 13px;
            max-width: 320px;
            line-height: 1.6
        }

        /* MODAL */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .6);
            z-index: 100;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px)
        }

        .overlay.open {
            display: flex
        }

        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 100%;
            max-width: 480px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 28px;
            animation: fadeIn .2s ease
        }

        .modal h2 {
            font-family: 'DM Serif Display', serif;
            font-size: 22px;
            margin-bottom: 4px
        }

        .modal .sub {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 22px
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 7px;
            margin-bottom: 16px
        }

        .field label {
            font-size: 11px;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .07em
        }

        .field input,
        .field select,
        .field textarea {
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 11px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text);
            outline: none;
            transition: border-color .18s;
            -webkit-appearance: none;
            appearance: none
        }

        .field select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%237a7d8a' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 36px
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: var(--accent)
        }

        .field-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px
        }

        .field-hint {
            font-size: 11px;
            color: var(--muted);
            margin-top: -4px
        }

        .modal-foot {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--border)
        }

        .color-pick {
            display: flex;
            gap: 8px;
            flex-wrap: wrap
        }

        .cp {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: transform .15s, border-color .15s
        }

        .cp:hover {
            transform: scale(1.15)
        }

        .cp.sel {
            border-color: #fff
        }

        /* MISC */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px
        }

        ::-webkit-scrollbar-track {
            background: transparent
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 4px
        }

        .sec-title {
            font-family: 'DM Serif Display', serif;
            font-size: 18px;
            margin-bottom: 16px
        }

        .sec-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px
        }

        .sec-row h2 {
            font-family: 'DM Serif Display', serif;
            font-size: 18px
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 7px 12px;
            font-size: 13px;
            color: var(--muted)
        }

        .search-box input {
            background: transparent;
            border: none;
            outline: none;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            width: 180px
        }

        .notif-list {
            display: flex;
            flex-direction: column;
            gap: 2px
        }

        .notif-item {
            display: flex;
            gap: 12px;
            padding: 13px 16px;
            border-radius: 10px;
            transition: background .15s
        }

        .notif-item:hover {
            background: var(--surface2)
        }

        .notif-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            flex-shrink: 0;
            margin-top: 5px
        }

        .notif-dot.read {
            background: var(--border)
        }

        .notif-body {
            flex: 1
        }

        .notif-msg {
            font-size: 13px;
            line-height: 1.5
        }

        .notif-time {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px
        }

        .avail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 12px
        }

        .avail-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px
        }

        .avail-card h4 {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px
        }

        .avail-card .cap {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 10px
        }

        .avail-bar-wrap {
            background: var(--surface2);
            border-radius: 4px;
            height: 6px;
            overflow: hidden
        }

        .avail-bar {
            height: 100%;
            border-radius: 4px;
            background: var(--accent)
        }

        .avail-bar.busy {
            background: var(--danger)
        }
    </style>
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
            <button class="ni active" onclick="go('home',this)"><span class="ni-ic">▦</span> Resumen</button>
            <button class="ni" onclick="go('horario',this)"><span class="ni-ic">▤</span> Horarios</button>
            <span class="sb-label">Administrar</span>
            <button class="ni" onclick="go('usuarios',this)"><span class="ni-ic">◻</span> Usuarios</button>
            <button class="ni" onclick="go('grupos',this)"><span class="ni-ic">◻</span> Grupos</button>
            <button class="ni" onclick="go('salones',this)"><span class="ni-ic">◻</span> Salones</button>
            <span class="sb-label">Sistema</span>
            <button class="ni" onclick="go('solicitudes',this)"><span class="ni-ic">◻</span> Solicitudes<span class="nb"
                    id="sol-badge">0</span></button>
            <button class="ni" onclick="go('notificaciones',this)"><span class="ni-ic">◻</span> Notificaciones<span
                    class="nb" id="notif-badge">0</span></button>
        </nav>
        <div class="sb-foot">
            <div class="user-row">
                <div class="av">AD</div>
                <div class="u-info">
                    <div class="u-name">Administrador</div>
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
                    <div class="stat-card"><span class="label">Usuarios</span><span class="value"
                            id="count-usuarios">—</span><span class="sub">Registrados</span></div>
                    <div class="stat-card"><span class="label">Profesores</span><span class="value"
                            id="count-profesores">—</span><span class="sub">Activos</span></div>
                    <div class="stat-card"><span class="label">Grupos</span><span class="value"
                            id="count-grupos">—</span><span class="sub">Creados</span></div>
                    <div class="stat-card"><span class="label">Salones</span><span class="value"
                            id="count-salones">—</span><span class="sub">Registrados</span></div>
                    <div class="stat-card accent"><span class="label">Horarios activos</span><span class="value"
                            id="count-horarios">—</span><span class="sub">Esta semana</span></div>
                    <div class="stat-card"><span class="label">Solicitudes</span><span class="value"
                            id="count-solicitudes">—</span><span class="sub">Pendientes</span></div>
                </div>
                <div class="table-wrap">
                    <div class="table-head">
                        <h3>Actividad reciente</h3>
                    </div>
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
                        <p>Primero crea un grupo en la sección <strong>Grupos</strong> para poder gestionar su horario.
                        </p>
                        <button class="btn btn-p" style="margin-top:8px"
                            onclick="go('grupos', document.querySelectorAll('.ni')[3])">Ir a Grupos</button>
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
                    <p style="font-size:12px;color:var(--muted);margin-top:10px">Haz clic en cualquier celda vacía para
                        agregar un bloque. Los bloques se guardan por grupo.</p>
                </div>
            </div>

            <!-- USUARIOS -->
            <div class="panel" id="panel-usuarios">
                <div class="sec-row">
                    <h2>Usuarios</h2>
                    <div style="display:flex;gap:8px">
                        <div class="search-box"><span>⌕</span><input id="usr-search" placeholder="Buscar usuario…"
                                oninput="filterTable('usr-tbody','usr-search')" /></div>
                        <button class="btn btn-p" onclick="openModal('modal-usuario')">+ Nuevo usuario</button>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Nickname</th>
                                <th>Correo</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="usr-tbody">
                            <tr>
                                <td colspan="6" style="text-align:center;color:var(--muted);padding:30px">No hay
                                    usuarios registrados aún.</td>
                            </tr>
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
                                <th>Nombre / Salón</th>
                                <th>Descripción</th>
                                <th>Miembros</th>
                                <th>Profesor asignado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="grp-tbody">
                            <tr id="grp-empty-row">
                                <td colspan="5" style="text-align:center;color:var(--muted);padding:30px">No hay grupos
                                    creados. Usa <strong>+ Nuevo grupo</strong> para comenzar.</td>
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
                <h3 style="font-size:13px;color:var(--muted);margin-bottom:14px;font-weight:400">Disponibilidad actual
                </h3>
                <div class="avail-grid" id="avail-grid">
                    <p style="color:var(--muted);font-size:13px;grid-column:1/-1">No hay salones registrados aún.</p>
                </div>
                <div style="margin-top:22px">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Capacidad</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="sal-tbody">
                                <tr>
                                    <td colspan="4" style="text-align:center;color:var(--muted);padding:30px">No hay
                                        salones registrados aún.</td>
                                </tr>
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
                                <th>Usuario</th>
                                <th>Rol actual</th>
                                <th>Rol solicitado</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="sol-tbody">
                            <tr>
                                <td colspan="6" style="text-align:center;color:var(--muted);padding:30px">No hay
                                    solicitudes pendientes.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- NOTIFICACIONES -->
            <div class="panel" id="panel-notificaciones">
                <div class="sec-row">
                    <h2>Notificaciones</h2>
                    <button class="btn btn-o" onclick="markAllRead()">Marcar todas leídas</button>
                </div>
                <div class="table-wrap">
                    <div class="notif-list" id="notif-list" style="padding:6px 0">
                        <p style="color:var(--muted);padding:20px 16px;font-size:13px">No hay notificaciones.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MODALS -->

    <!-- Modal: Bloque -->
    <div class="overlay" id="modal-bloque">
        <div class="modal">
            <h2 id="bloque-modal-title">Agregar bloque</h2>
            <p class="sub">Completa los datos del bloque de clase</p>
            <div class="field"><label>Materia / Nombre</label><input id="blk-nombre" placeholder="Ej: Matemáticas" />
            </div>
            <div class="field-row">
                <div class="field"><label>Día</label>
                    <select id="blk-dia">
                        <option value="lunes">Lunes</option>
                        <option value="martes">Martes</option>
                        <option value="miercoles">Miércoles</option>
                        <option value="jueves">Jueves</option>
                        <option value="viernes">Viernes</option>
                        <option value="sabado">Sábado</option>
                    </select>
                </div>
                <div class="field"><label>Salón</label>
                    <select id="blk-salon">
                        <option value="">— Sin salones —</option>
                    </select>
                </div>
            </div>
            <div class="field-row">
                <div class="field"><label>Hora inicio</label><input type="time" id="blk-inicio" value="07:00" /></div>
                <div class="field"><label>Hora fin</label><input type="time" id="blk-fin" value="09:00" /></div>
            </div>
            <div class="field"><label>Profesor</label>
                <select id="blk-prof">
                    <option value="Sin asignar">Sin asignar</option>
                </select>
            </div>
            <div class="field"><label>Color del bloque</label>
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
            <div class="field"><label>Correo</label><input type="email" id="usr-correo" placeholder="juan@correo.co" />
            </div>
            <div class="field"><label>Rol</label>
                <select id="usr-rol">
                    <option value="3">Estudiante</option>
                    <option value="2">Profesor</option>
                    <option value="1">Administrador</option>
                </select>
            </div>
            <div class="field"><label>Contraseña</label><input type="password" id="usr-pass" placeholder="••••••••" />
            </div>
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
                <input id="grp-nombre" placeholder="Ej: 11-B, 10-2, Grado 9A, 3141033…" />
                <span class="field-hint">Usa el formato que maneja tu institución (letras, números o
                    combinación).</span>
            </div>
            <div class="field"><label>Descripción</label><textarea id="grp-desc" rows="3"
                    placeholder="Descripción del grupo…"></textarea></div>
            <div class="field"><label>Profesor asignado</label>
                <select id="grp-prof">
                    <option value="">Sin asignar</option>
                </select>
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
            <div class="field"><label>Nombre del salón</label><input id="sal-nombre"
                    placeholder="Ej: Lab 104, Aula 3…" /></div>
            <div class="field"><label>Capacidad</label><input type="number" id="sal-cap" placeholder="30" min="1" />
            </div>
            <div class="modal-foot">
                <button class="btn btn-o" onclick="closeModal('modal-salon')">Cancelar</button>
                <button class="btn btn-p" onclick="saveSalon()">Guardar</button>
            </div>
        </div>
    </div>

    <script>
        const COLORS = { c0: '#c8a96e', c1: '#64a0dc', c2: '#78c88c', c3: '#c878b4', c4: '#dc9664', c5: '#a08cdc', c6: '#60c8c0', c7: '#e0b060' };

        // Estado de la app (todo vacío al inicio)
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

        /* PANEL HORARIO */
        function renderSchedPanel() {
            const noGroups = document.getElementById('sched-no-groups');
            const schedCont = document.getElementById('sched-content');
            if (grupos.length === 0) {
                noGroups.style.display = 'block';
                schedCont.style.display = 'none';
                return;
            }
            noGroups.style.display = 'none';
            schedCont.style.display = 'block';
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

        /* BLOQUES CRUD */
        function abrirModalBloque(fromCell) {
            editingBlock = null;
            document.getElementById('bloque-modal-title').textContent = 'Agregar bloque';
            document.getElementById('blk-nombre').value = '';
            document.getElementById('blk-inicio').value = '07:00';
            document.getElementById('blk-fin').value = '09:00';
            if (fromCell && clickedCell) {
                document.getElementById('blk-dia').value = clickedCell.dia;
                document.getElementById('blk-inicio').value = clickedCell.hora;
                const h = parseInt(clickedCell.hora);
                document.getElementById('blk-fin').value = String(h + 1).padStart(2, '0') + ':00';
            }
            openModal('modal-bloque');
        }

        function addBlock() {
            const nombre = document.getElementById('blk-nombre').value.trim();
            const dia = document.getElementById('blk-dia').value;
            const salon = document.getElementById('blk-salon').value;
            const inicio = document.getElementById('blk-inicio').value;
            const fin = document.getElementById('blk-fin').value;
            const profesor = document.getElementById('blk-prof').value;
            if (!nombre || !inicio || !fin) { alert('Completa todos los campos.'); return; }
            if (inicio >= fin) { alert('La hora inicio debe ser antes de la hora fin.'); return; }
            const g = currentGrupo();
            if (!schedData[g]) schedData[g] = [];
            if (editingBlock !== null) {
                schedData[g][editingBlock] = { dia, hora_inicio: inicio, hora_fin: fin, nombre, salon, profesor, color: selectedColor };
                editingBlock = null;
            } else {
                schedData[g].push({ dia, hora_inicio: inicio, hora_fin: fin, nombre, salon, profesor, color: selectedColor });
            }
            closeModal('modal-bloque');
            renderSched();
            // TODO: POST /api/horarios
        }

        function editBlock(idx) {
            const g = currentGrupo();
            const b = schedData[g][idx];
            editingBlock = idx;
            document.getElementById('bloque-modal-title').textContent = 'Editar bloque';
            document.getElementById('blk-nombre').value = b.nombre;
            document.getElementById('blk-dia').value = b.dia;
            document.getElementById('blk-salon').value = b.salon;
            document.getElementById('blk-inicio').value = b.hora_inicio;
            document.getElementById('blk-fin').value = b.hora_fin;
            document.getElementById('blk-prof').value = b.profesor;
            selectedColor = b.color || 'c0';
            document.querySelectorAll('.cp').forEach(c => c.classList.toggle('sel', c.dataset.c === selectedColor));
            openModal('modal-bloque');
        }

        function deleteBlock(e, idx) {
            e.stopPropagation();
            if (!confirm('¿Eliminar este bloque?')) return;
            schedData[currentGrupo()].splice(idx, 1);
            renderSched();
        }

        function clearSched() {
            if (!confirm('¿Limpiar todo el horario de este grupo?')) return;
            schedData[currentGrupo()] = [];
            renderSched();
        }

        /* MODALS */
        function openModal(id) { document.getElementById(id).classList.add('open'); }
        function closeModal(id) { document.getElementById(id).classList.remove('open'); clickedCell = null; }

        document.querySelectorAll('.overlay').forEach(o => {
            o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
        });

        function selectColor(el) {
            document.querySelectorAll('.cp').forEach(c => c.classList.remove('sel'));
            el.classList.add('sel');
            selectedColor = el.dataset.c;
        }

        /* USUARIOS */
        function saveUsuario() {
            const n = document.getElementById('usr-nombre').value.trim();
            const a = document.getElementById('usr-apellido').value.trim();
            if (!n || !a) { alert('Completa nombre y apellido.'); return; }
            const rolVal = document.getElementById('usr-rol').value;
            const rolMap = { '1': 'Admin', '2': 'Profesor', '3': 'Estudiante' };
            const badgeMap = { '1': 'badge-admin', '2': 'badge-prof', '3': 'badge-est' };
            const emptyRow = document.querySelector('#usr-tbody td[colspan]');
            if (emptyRow) emptyRow.closest('tr').remove();
            const tbody = document.getElementById('usr-tbody');
            const tr = document.createElement('tr');
            tr.innerHTML = `
  <td>${n} ${a}</td>
  <td>${document.getElementById('usr-nick').value}</td>
  <td>${document.getElementById('usr-correo').value}</td>
  <td><span class="badge ${badgeMap[rolVal]}">${rolMap[rolVal]}</span></td>
  <td><span class="badge badge-act">Activo</span></td>
  <td><div class="actions-cell"><button class="btn btn-o btn-sm">Editar</button><button class="btn btn-d btn-sm">Desactivar</button></div></td>`;
            tbody.appendChild(tr);
            if (rolVal === '2') {
                const nombre = `${n} ${a}`;
                ['grp-prof', 'blk-prof'].forEach(id => {
                    const sel = document.getElementById(id);
                    const opt = document.createElement('option');
                    opt.value = opt.textContent = nombre;
                    sel.appendChild(opt);
                });
            }
            closeModal('modal-usuario');
            ['usr-nombre', 'usr-apellido', 'usr-nick', 'usr-correo', 'usr-pass'].forEach(id => document.getElementById(id).value = '');
            // TODO: POST /api/usuarios
        }

        /* GRUPOS */
        function saveGrupo() {
            const n = document.getElementById('grp-nombre').value.trim();
            if (!n) { alert('El nombre del grupo es requerido.'); return; }
            const id = 'grp-' + Date.now();
            const desc = document.getElementById('grp-desc').value.trim();
            const prof = document.getElementById('grp-prof').value || 'Sin asignar';
            grupos.push({ id, nombre: n, desc, prof });
            schedData[id] = [];
            const emptyRow = document.getElementById('grp-empty-row');
            if (emptyRow) emptyRow.remove();
            const tbody = document.getElementById('grp-tbody');
            const tr = document.createElement('tr');
            tr.dataset.groupId = id;
            tr.innerHTML = `
  <td><strong>${n}</strong></td>
  <td>${desc || '—'}</td>
  <td>0</td>
  <td>${prof}</td>
  <td><div class="actions-cell">
    <button class="btn btn-o btn-sm">Editar</button>
    <button class="btn btn-o btn-sm">Miembros</button>
    <button class="btn btn-d btn-sm" onclick="deleteGrupo('${id}',this)">Eliminar</button>
  </div></td>`;
            tbody.appendChild(tr);
            closeModal('modal-grupo');
            document.getElementById('grp-nombre').value = '';
            document.getElementById('grp-desc').value = '';
            // TODO: POST /api/grupos
        }

        function deleteGrupo(id, btn) {
            if (!confirm('¿Eliminar este grupo? Su horario también se borrará.')) return;
            const idx = grupos.findIndex(g => g.id === id);
            if (idx > -1) grupos.splice(idx, 1);
            delete schedData[id];
            btn.closest('tr').remove();
            if (document.getElementById('grp-tbody').children.length === 0) {
                const tr = document.createElement('tr');
                tr.id = 'grp-empty-row';
                tr.innerHTML = `<td colspan="5" style="text-align:center;color:var(--muted);padding:30px">No hay grupos creados. Usa <strong>+ Nuevo grupo</strong> para comenzar.</td>`;
                document.getElementById('grp-tbody').appendChild(tr);
            }
        }

        /* SALONES */
        function saveSalon() {
            const n = document.getElementById('sal-nombre').value.trim();
            const c = parseInt(document.getElementById('sal-cap').value);
            if (!n || !c || c < 1) { alert('Completa todos los campos correctamente.'); return; }
            salonesData.push({ nombre: n, cap: c, occ: 0 });
            const emptyRow = document.querySelector('#sal-tbody td[colspan]');
            if (emptyRow) emptyRow.closest('tr').remove();
            const tbody = document.getElementById('sal-tbody');
            const tr = document.createElement('tr');
            tr.innerHTML = `
  <td>${n}</td><td>${c}</td>
  <td><span class="badge badge-act">Disponible</span></td>
  <td><div class="actions-cell"><button class="btn btn-o btn-sm">Editar</button><button class="btn btn-d btn-sm">Eliminar</button></div></td>`;
            tbody.appendChild(tr);
            const blkSalon = document.getElementById('blk-salon');
            const firstOpt = blkSalon.querySelector('option[value=""]');
            if (firstOpt) firstOpt.remove();
            const opt = document.createElement('option');
            opt.value = opt.textContent = n;
            blkSalon.appendChild(opt);
            renderAvail();
            closeModal('modal-salon');
            document.getElementById('sal-nombre').value = '';
            document.getElementById('sal-cap').value = '';
        }

        /* SOLICITUDES */
        function approveSol(btn) {
            const tr = btn.closest('tr');
            tr.querySelector('td:nth-child(5)').innerHTML = '<span class="badge badge-act">Aprobado</span>';
            tr.querySelector('.actions-cell').innerHTML = '';
            const b = document.getElementById('sol-badge');
            if (b) { const n = parseInt(b.textContent) - 1; if (n <= 0) b.remove(); else b.textContent = n; }
        }
        function rejectSol(btn) {
            const tr = btn.closest('tr');
            tr.querySelector('td:nth-child(5)').innerHTML = '<span class="badge badge-inac">Rechazado</span>';
            tr.querySelector('.actions-cell').innerHTML = '';
            const b = document.getElementById('sol-badge');
            if (b) { const n = parseInt(b.textContent) - 1; if (n <= 0) b.remove(); else b.textContent = n; }
        }

        /* NOTIFICACIONES */
        function markAllRead() {
            document.querySelectorAll('.notif-dot').forEach(d => d.classList.add('read'));
        }

        /* BÚSQUEDA */
        function filterTable(tbodyId, inputId) {
            const q = document.getElementById(inputId).value.toLowerCase();
            document.querySelectorAll('#' + tbodyId + ' tr').forEach(tr => {
                tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }

        /* DISPONIBILIDAD */
        function renderAvail() {
            const g = document.getElementById('avail-grid');
            if (salonesData.length === 0) {
                g.innerHTML = '<p style="color:var(--muted);font-size:13px;grid-column:1/-1">No hay salones registrados aún.</p>';
                return;
            }
            g.innerHTML = salonesData.map(s => {
                const pct = Math.round(s.occ / s.cap * 100);
                const busy = pct >= 100;
                return `<div class="avail-card"><h4>${s.nombre}</h4><div class="cap">${s.occ}/${s.cap} lugares · ${pct}%</div><div class="avail-bar-wrap"><div class="avail-bar${busy ? ' busy' : ''}" style="width:${Math.min(pct, 100)}%"></div></div></div>`;
            }).join('');
        }

        /* BACKEND */
        const cargarEstadisticas = async () => {
            try {
                // Ajusta la ruta si tu admin.html está en una subcarpeta
                const resp = await fetch('api/v1/admin/stats.php');
                if (!resp.ok) throw new Error('Error en la red');

                const data = await resp.json();

                // Mapeo de lo que envía PHP vs IDs en el HTML
                const ids = {
                    total_usuarios: 'count-usuarios',
                    total_profesores: 'count-profesores',
                    total_solicitudes: 'count-solicitudes',
                    total_salones: 'count-salones', // Ahora coincide con la tabla 'aulas'
                    total_horarios: 'count-horarios'  // Ahora coincide con la tabla 'clases'
                };

                Object.keys(ids).forEach(k => {
                    const el = document.getElementById(ids[k]);
                    if (el && data[k] !== undefined) {
                        el.textContent = data[k];
                    }
                });
            } catch (e) {
                console.error('Error al cargar stats:', e);
            }
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
                    <div class="notif-msg">
                        <strong>${item.nombre}</strong> solicitó cambio de rol a <strong>${item.nombre_rol}</strong>
                    </div>
                    <div class="notif-time">
                        Estado: <span class="badge ${item.estado === 'pendiente' ? 'badge-warn' : 'badge-act'}">${item.estado}</span> 
                        · ${item.created_at}
                    </div>
                </div>
            </div>`).join('');
            } catch (e) {
                lista.innerHTML = '<p style="color:var(--muted);padding:16px;font-size:13px">Error al conectar con la API.</p>';
            }
        };
    </script>
</body>

</html>