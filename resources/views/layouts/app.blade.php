<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'BranchNet NOC' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>.flash{margin:18px 42px 0;padding:12px 16px;border-radius:8px}.flash.success{background:#183326;color:#5be49b}.flash.error{background:#3b2027;color:#ff6c78}.form-card{max-width:900px;background:var(--panel);border:1px solid var(--line);border-radius:10px;padding:26px}.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:17px}.form-grid label{display:flex;flex-direction:column;gap:7px;color:var(--muted);font-size:12px}.form-grid input,.form-grid select,.form-grid textarea{width:100%;font-size:14px}.form-grid textarea{min-height:100px;resize:vertical}.form-grid .wide{grid-column:1/-1}.form-grid .check{flex-direction:row;align-items:center}.form-grid .check input{width:auto}.form-actions{display:flex;justify-content:flex-end;gap:12px;margin-top:25px;align-items:center}.actions{white-space:nowrap}.actions a,.actions button{background:none;border:0;color:var(--blue);cursor:pointer;text-decoration:none;font:12px 'DM Sans';margin-right:8px}.actions form{display:inline}.danger-button{background:#3b2027;color:var(--red);border:1px solid #67333b;border-radius:7px;padding:8px 12px;cursor:pointer;margin-left:8px}.inline{display:inline}.detail-actions,.toolbar-actions{display:flex;align-items:center;gap:10px}.detail-actions{justify-content:space-between}.detail-actions>span{display:flex;align-items:center;gap:8px}.settings-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.info-card{margin-top:14px}.info-card h3{margin-top:0}.info-card code{display:block;color:var(--green);background:var(--bg);padding:10px;margin-top:8px;border-radius:5px}@media(max-width:700px){.form-grid{grid-template-columns:1fr}.form-grid .wide{grid-column:auto}.toolbar,.toolbar-actions{align-items:flex-start;flex-direction:column}.detail-actions{align-items:flex-start;gap:14px;flex-direction:column}.settings-grid{grid-template-columns:1fr}}html[data-theme=light]{--bg:#f3f7fa;--panel:#fff;--panel2:#e7eef3;--line:#d2dee6;--text:#182733;--muted:#5d7180;--green:#168b58;--yellow:#a66b00;--red:#c33446;--blue:#1769aa}html[data-theme=light] body{background:var(--bg)}html[data-theme=light] .sidebar{background:#eaf1f5}html[data-theme=light] .topbar{background:#fff}html[data-theme=light] .topo-node{background:#e9f8ef}html[data-theme=light] .topo-node.warning{background:#fff7df}html[data-theme=light] .topo-node.offline{background:#ffebee}html[data-theme=light] .info-card code{background:#eef4f7}@media(max-width:700px){.form-grid{grid-template-columns:1fr}.form-grid .wide{grid-column:auto}.toolbar,.toolbar-actions{align-items:flex-start;flex-direction:column}.detail-actions{align-items:flex-start;gap:14px;flex-direction:column}}</style>
</head>
<body>
<div class="shell">
    <aside class="sidebar">
        <div class="brand"><span class="brand-mark">⌁</span><span>BRANCH<span class="accent">NET</span></span></div>
        <div class="eyebrow">NETWORK OPERATIONS</div>
        <nav>
            <a class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">▦ <span>Dashboard</span></a>
            <a class="nav-item {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">◈ <span>Branches</span></a>
            <a class="nav-item {{ request()->routeIs('agents.*') ? 'active' : '' }}" href="{{ route('agents.index') }}">◌ <span>Agents</span></a>
            <a class="nav-item {{ request()->routeIs('devices.*') ? 'active' : '' }}" href="{{ route('devices.index') }}">⌘ <span>Devices</span></a>
            <a class="nav-item {{ request()->routeIs('network-map') ? 'active' : '' }}" href="{{ route('network-map') }}">⌬ <span>Network Map</span></a>
            <a class="nav-item {{ request()->routeIs('alerts.*') ? 'active' : '' }}" href="{{ route('alerts.index') }}">⚠ <span>Alerts</span><span class="nav-badge">{{ $activeAlerts ?? '' }}</span></a>
            <a class="nav-item {{ request()->routeIs('monitoring-history') ? 'active' : '' }}" href="{{ route('monitoring-history') }}">◷ <span>Monitoring History</span></a>
            <a class="nav-item" href="/pulse">◉ <span>Laravel Pulse</span></a>
        </nav>
        <div class="sidebar-bottom"><div class="eyebrow">SYSTEM</div><a class="nav-item {{ request()->routeIs('settings') ? 'active' : '' }}" href="{{ route('settings') }}">⚙ <span>Settings</span></a><a class="nav-item {{ request()->routeIs('audit-logs') ? 'active' : '' }}" href="{{ route('audit-logs') }}">◉ <span>Audit Logs</span></a></div>
    </aside>
    <main class="main"><header class="topbar"><div><div class="eyebrow">CENTRAL MONITORING</div><h1>{{ $heading ?? 'Network overview' }}</h1></div><div class="top-actions"><span class="live-dot"></span><span>Live · {{ now()->format('H:i:s') }}</span><button class="icon-button" id="theme-toggle" type="button" title="Toggle light mode">☼</button><button class="icon-button">⌕</button><div class="avatar">IT</div></div></header>@if(session('success'))<div class="flash success">{{ session('success') }}</div>@endif@if($errors->any())<div class="flash error">{{ $errors->first() }}</div>@endif @yield('content')</main>
</div>
<script>(function(){const root=document.documentElement, key='branchnet-theme';const saved=localStorage.getItem(key);if(saved)root.dataset.theme=saved;const button=document.getElementById('theme-toggle');if(button)button.addEventListener('click',()=>{const next=root.dataset.theme==='light'?'dark':'light';root.dataset.theme=next;localStorage.setItem(key,next)});})();</script>
</body>
</html>
