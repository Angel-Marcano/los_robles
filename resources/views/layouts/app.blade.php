<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<title>{{ $appName ?? 'Los Robles' }}</title>
	<!-- Bootstrap 5.3 -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Bootstrap Icons -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
	<style>
		/* ── Theme transition ── */
		html { transition: background-color .25s ease, color .25s ease; }

		/* ── Layout shell ── */
		.lr-shell { display: flex; min-height: 100vh; }
		.lr-sidebar {
			width: 260px; min-height: 100vh; position: sticky; top: 0; align-self: flex-start;
			background: var(--bs-body-bg); border-right: 1px solid var(--bs-border-color);
			display: flex; flex-direction: column; z-index: 1040; transition: transform .25s ease;
		}
		[data-bs-theme="dark"] .lr-sidebar { background: #1a1d21; border-right-color: rgba(255,255,255,.06); }
		.lr-sidebar-brand {
			padding: 1rem 1.25rem; display: flex; align-items: center; gap: .5rem;
			font-weight: 700; font-size: 1.1rem; letter-spacing: -.5px; text-decoration: none; color: inherit;
			border-bottom: 1px solid var(--bs-border-color); flex-shrink: 0;
		}
		.lr-sidebar-brand i { color: var(--bs-success); }
		.lr-sidebar-nav { flex-grow: 1; overflow-y: auto; padding: .75rem 0; }
		.lr-sidebar-section { margin-bottom: .25rem; }
		.lr-sidebar-heading {
			font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .8px;
			color: var(--bs-secondary-color); padding: .75rem 1.25rem .25rem;
		}
		.lr-sidebar-link {
			display: flex; align-items: center; gap: .6rem; padding: .5rem 1.25rem;
			font-size: .85rem; font-weight: 500; color: var(--bs-body-color); text-decoration: none;
			border-left: 3px solid transparent; transition: all .15s ease;
		}
		.lr-sidebar-link:hover { background: rgba(var(--bs-primary-rgb),.08); border-left-color: rgba(var(--bs-primary-rgb),.3); }
		.lr-sidebar-link.active { background: rgba(var(--bs-primary-rgb),.12); border-left-color: var(--bs-primary); color: var(--bs-primary); font-weight: 600; }
		.lr-sidebar-link i { font-size: 1rem; width: 1.25rem; text-align: center; flex-shrink: 0; }
		.lr-sidebar-toggle {
			display: flex; align-items: center; gap: .6rem; padding: .5rem 1.25rem; width: 100%;
			font-size: .85rem; font-weight: 600; color: var(--bs-body-color); background: none; border: none;
			border-left: 3px solid transparent; transition: all .15s ease; cursor: pointer;
		}
		.lr-sidebar-toggle:hover { background: rgba(var(--bs-primary-rgb),.08); }
		.lr-sidebar-toggle i.bi-chevron { margin-left: auto; font-size: .75rem; transition: transform .2s ease; }
		.lr-sidebar-toggle[aria-expanded="true"] i.bi-chevron { transform: rotate(90deg); }
		.lr-sidebar-sub .lr-sidebar-link { padding-left: 2.6rem; font-size: .8rem; font-weight: 400; }
		.lr-sidebar-footer {
			padding: .75rem 1.25rem; border-top: 1px solid var(--bs-border-color); flex-shrink: 0;
			display: flex; align-items: center; justify-content: space-between; gap: .5rem;
		}
		.lr-main { flex-grow: 1; display: flex; flex-direction: column; min-width: 0; }
		.lr-topbar {
			padding: .5rem 1.25rem; border-bottom: 1px solid var(--bs-border-color);
			display: flex; align-items: center; justify-content: space-between; gap: 1rem;
			background: var(--bs-body-bg); position: sticky; top: 0; z-index: 1030;
		}
		[data-bs-theme="dark"] .lr-topbar { background: #1a1d21; }
		.lr-sidebar-backdrop {
			position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1035; display: none;
		}
		.lr-sidebar-backdrop.show { display: block; }

		/* ── Cards ── */
		.card { border: none; border-radius: .75rem; box-shadow: 0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04); }
		[data-bs-theme="dark"] .card { box-shadow: 0 1px 3px rgba(0,0,0,.3); }

		/* ── Tables ── */
		.table { --bs-table-border-color: rgba(0,0,0,.06); border-collapse: separate; border-spacing: 0; }
		[data-bs-theme="dark"] .table { --bs-table-border-color: rgba(255,255,255,.06); }
		.table thead th { font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--bs-secondary-color); border-bottom-width: 2px; padding: .65rem .75rem; background: transparent; }
		.table tbody td { padding: .6rem .75rem; vertical-align: middle; }
		.table-hover tbody tr { transition: background-color .15s ease; }
		.table-row-link { cursor: pointer; }

		/* ── Badges ── */
		.badge { font-weight: 500; font-size: .75rem; padding: .35em .65em; border-radius: 50rem; }

		/* ── Buttons ── */
		.btn { font-size: .85rem; font-weight: 500; border-radius: .5rem; transition: all .2s ease; }
		.btn-sm { font-size: .78rem; padding: .3rem .65rem; }
		.btn-action { display: inline-flex; align-items: center; gap: .35rem; }

		/* ── Page header ── */
		.page-header { margin-bottom: 1.5rem; }
		.page-header h1 { font-size: 1.5rem; font-weight: 700; margin: 0; }
		.page-header .text-muted { font-size: .85rem; }

		/* ── Alerts ── */
		.alert { border: none; border-radius: .75rem; font-size: .875rem; }

		/* ── Forms ── */
		.form-control, .form-select { border-radius: .5rem; font-size: .875rem; }
		.form-label { font-weight: 600; font-size: .8rem; text-transform: uppercase; letter-spacing: .3px; color: var(--bs-secondary-color); margin-bottom: .3rem; }

		/* ── Pagination ── */
		.pagination { --bs-pagination-border-radius: .5rem; }
		.page-link { border-radius: .375rem !important; margin: 0 2px; font-size: .85rem; }

		/* ── Theme toggle ── */
		.theme-toggle { background: none; border: 1px solid var(--bs-border-color); border-radius: .5rem; padding: .35rem .55rem; color: var(--bs-body-color); cursor: pointer; transition: all .2s; display: flex; align-items: center; }
		.theme-toggle:hover { background: rgba(var(--bs-primary-rgb),.1); border-color: var(--bs-primary); color: var(--bs-primary); }

		/* ── Misc ── */
		.empty-state { text-align: center; padding: 3rem 1rem; color: var(--bs-secondary-color); }
		.empty-state i { font-size: 3rem; margin-bottom: .75rem; display: block; opacity: .5; }

		/* ── Print ── */
		@media print {
			.lr-sidebar, .lr-topbar, .theme-toggle, .no-print { display: none !important; }
			.card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
		}

		/* ── Mobile sidebar ── */
		.lr-sidebar-mobile-btn { display: none; }
		@media (max-width: 991.98px) {
			.lr-sidebar {
				position: fixed; left: 0; top: 0; bottom: 0; min-height: 100vh; transform: translateX(-100%);
			}
			.lr-sidebar.show { transform: translateX(0); }
			.lr-sidebar-mobile-btn { display: inline-flex; }
		}
	</style>
	@stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
@if (!Request::is('login'))
@php
	$isAdmin = auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('condo_admin') || auth()->user()->hasRole('tower_admin'));
	$isSuperAdmin = auth()->check() && auth()->user()->hasRole('super_admin');
	$isCondoAdmin = auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('condo_admin'));
@endphp

<div class="lr-shell">
	<!-- Sidebar -->
	<aside class="lr-sidebar" id="lrSidebar">
		<a class="lr-sidebar-brand" href="{{ route('dashboard') }}">
			<i class="bi bi-buildings"></i> {{ $appName ?? 'Los Robles' }}
		</a>
		<nav class="lr-sidebar-nav" id="lrSidebarNav">
			@php
				$activeSection = '';
				if (Request::routeIs('invoices.*') || Request::routeIs('payments.*')) $activeSection = 'facturacion';
				elseif (Request::routeIs('towers.*') || Request::routeIs('apartments.*') || Request::routeIs('ownerships.*')) $activeSection = 'estructura';
				elseif (Request::routeIs('users.*')) $activeSection = 'estructura';
				elseif (Request::routeIs('accounts.*') || Request::routeIs('exchange.*') || Request::routeIs('reserve-funds.*')) $activeSection = 'finanzas';
				elseif (Request::routeIs('rates.*')) $activeSection = 'configuracion';
				elseif (Request::routeIs('reports.*')) $activeSection = 'reportes';
				elseif (Request::routeIs('assemblies.*')) $activeSection = 'asambleas';
				elseif (Request::routeIs('audit-logs*') || Request::routeIs('chatbot.admin.*')) $activeSection = 'admin';
			@endphp

			<!-- Facturación -->
			<div class="lr-sidebar-section">
				<button class="lr-sidebar-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sec-facturacion" aria-expanded="{{ $activeSection === 'facturacion' ? 'true' : 'false' }}">
					<i class="bi bi-file-earmark-text"></i> Facturación
					<i class="bi bi-chevron-right bi-chevron"></i>
				</button>
				<div class="collapse lr-sidebar-sub" id="sec-facturacion" data-bs-parent="#lrSidebarNav">
					<a class="lr-sidebar-link {{ Request::routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
						<i class="bi bi-receipt"></i> Facturas
					</a>
					@if($isAdmin)
					<a class="lr-sidebar-link {{ Request::routeIs('expense-items.*') ? 'active' : '' }}" href="{{ route('expense-items.index') }}">
						<i class="bi bi-list-ul"></i> Elementos de cobro
					</a>
					@endif
				</div>
			</div>

			@if($isAdmin)
			<!-- Estructura -->
			<div class="lr-sidebar-section">
				<button class="lr-sidebar-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sec-estructura" aria-expanded="{{ $activeSection === 'estructura' ? 'true' : 'false' }}">
					<i class="bi bi-building-gear"></i> Estructura
					<i class="bi bi-chevron-right bi-chevron"></i>
				</button>
				<div class="collapse lr-sidebar-sub" id="sec-estructura" data-bs-parent="#lrSidebarNav">
					<a class="lr-sidebar-link {{ Request::routeIs('towers.*') || Request::routeIs('apartments.*') ? 'active' : '' }}" href="{{ route('towers.index') }}">
						<i class="bi bi-building"></i> Torres y Apartamentos
					</a>
					<a class="lr-sidebar-link {{ Request::routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
						<i class="bi bi-people"></i> Usuarios
					</a>
				</div>
			</div>

			<!-- Finanzas -->
			<div class="lr-sidebar-section">
				<button class="lr-sidebar-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sec-finanzas" aria-expanded="{{ $activeSection === 'finanzas' ? 'true' : 'false' }}">
					<i class="bi bi-cash-coin"></i> Finanzas
					<i class="bi bi-chevron-right bi-chevron"></i>
				</button>
				<div class="collapse lr-sidebar-sub" id="sec-finanzas" data-bs-parent="#lrSidebarNav">
					<a class="lr-sidebar-link {{ Request::routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}">
						<i class="bi bi-wallet2"></i> Cuentas
					</a>
					<a class="lr-sidebar-link {{ Request::routeIs('exchange.*') ? 'active' : '' }}" href="{{ route('exchange.create') }}">
						<i class="bi bi-arrow-left-right"></i> Cambio de divisas
					</a>
					<a class="lr-sidebar-link {{ Request::routeIs('reserve-funds.*') && !Request::routeIs('reserve-funds.config.*') ? 'active' : '' }}" href="{{ route('reserve-funds.index') }}">
						<i class="bi bi-piggy-bank"></i> Fondo de reserva
					</a>
					@if($isCondoAdmin)
					<a class="lr-sidebar-link {{ Request::routeIs('reserve-funds.config.*') ? 'active' : '' }}" href="{{ route('reserve-funds.config.edit') }}">
						<i class="bi bi-sliders"></i> Config. fondo reserva
					</a>
					@endif
				</div>
			</div>

			<!-- Reportes -->
			<div class="lr-sidebar-section">
				<button class="lr-sidebar-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sec-reportes" aria-expanded="{{ $activeSection === 'reportes' ? 'true' : 'false' }}">
					<i class="bi bi-bar-chart-line"></i> Reportes
					<i class="bi bi-chevron-right bi-chevron"></i>
				</button>
				<div class="collapse lr-sidebar-sub" id="sec-reportes" data-bs-parent="#lrSidebarNav">
					<a class="lr-sidebar-link {{ Request::routeIs('reports.debtorsMonthly*') ? 'active' : '' }}" href="{{ route('reports.debtorsMonthly') }}">
						<i class="bi bi-exclamation-triangle"></i> Morosidad mensual
					</a>
					<a class="lr-sidebar-link {{ Request::routeIs('reports.debtorsByTower*') ? 'active' : '' }}" href="{{ route('reports.debtorsByTower') }}">
						<i class="bi bi-building-exclamation"></i> Morosidad por torre
					</a>
				</div>
			</div>

			<!-- Asambleas / Votaciones -->
			<div class="lr-sidebar-section">
				<button class="lr-sidebar-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sec-asambleas" aria-expanded="{{ $activeSection === 'asambleas' ? 'true' : 'false' }}">
					<i class="bi bi-card-checklist"></i> Asambleas
					<i class="bi bi-chevron-right bi-chevron"></i>
				</button>
				<div class="collapse lr-sidebar-sub" id="sec-asambleas" data-bs-parent="#lrSidebarNav">
					@if($isAdmin)
					<a class="lr-sidebar-link {{ Request::routeIs('assemblies.index') || Request::routeIs('assemblies.create') || Request::routeIs('assemblies.show') || Request::routeIs('assemblies.edit') ? 'active' : '' }}" href="{{ route('assemblies.index') }}">
						<i class="bi bi-list-check"></i> Gestionar votaciones
					</a>
					@endif
					<a class="lr-sidebar-link {{ Request::routeIs('assemblies.my') ? 'active' : '' }}" href="{{ route('assemblies.my') }}">
						<i class="bi bi-hand-thumbs-up"></i> Mis votaciones
					</a>
				</div>
			</div>

			<!-- Configuración -->
			<div class="lr-sidebar-section">
				<button class="lr-sidebar-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sec-configuracion" aria-expanded="{{ $activeSection === 'configuracion' ? 'true' : 'false' }}">
					<i class="bi bi-gear"></i> Configuración
					<i class="bi bi-chevron-right bi-chevron"></i>
				</button>
				<div class="collapse lr-sidebar-sub" id="sec-configuracion" data-bs-parent="#lrSidebarNav">
					<a class="lr-sidebar-link {{ Request::routeIs('rates.*') ? 'active' : '' }}" href="{{ route('rates.index') }}">
						<i class="bi bi-currency-exchange"></i> Tasas de cambio
					</a>
					@if($isSuperAdmin)
					<a class="lr-sidebar-link {{ Request::routeIs('condominiums.*') ? 'active' : '' }}" href="{{ route('condominiums.index') }}">
						<i class="bi bi-buildings"></i> Condominios
					</a>
					@endif
				</div>
			</div>

			<!-- Administración -->
			@if($isCondoAdmin)
			<div class="lr-sidebar-section">
				<button class="lr-sidebar-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#sec-admin" aria-expanded="{{ $activeSection === 'admin' ? 'true' : 'false' }}">
					<i class="bi bi-shield-lock"></i> Administración
					<i class="bi bi-chevron-right bi-chevron"></i>
				</button>
				<div class="collapse lr-sidebar-sub" id="sec-admin" data-bs-parent="#lrSidebarNav">
					<a class="lr-sidebar-link {{ Request::routeIs('audit-logs*') ? 'active' : '' }}" href="{{ route('audit.logs.index') }}">
						<i class="bi bi-clipboard-check"></i> Auditoría
					</a>
					{{-- Chatbot oculto por solicitud del usuario
					<a class="lr-sidebar-link {{ Request::routeIs('chatbot.admin.*') ? 'active' : '' }}" href="{{ route('chatbot.admin.conversations') }}">
						<i class="bi bi-chat-dots"></i> Conversaciones chatbot
					</a>
					--}}
				</div>
			</div>
			@endif
			@endif
		</nav>
		<div class="lr-sidebar-footer">
			<div class="dropdown">
				<button class="btn btn-sm btn-outline-secondary btn-action dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" aria-expanded="false">
					<i class="bi bi-person-circle"></i>
					<span class="text-truncate">{{ auth()->user()->name ?? '' }}</span>
				</button>
				<ul class="dropdown-menu dropdown-menu-start w-100">
					<li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i>Mi Perfil</a></li>
					<li><hr class="dropdown-divider"></li>
					<li>
						<form method="POST" action="{{ route('logout') }}">
							@csrf
							<button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Salir</button>
						</form>
					</li>
				</ul>
			</div>
			<button class="theme-toggle" id="themeToggle" type="button" title="Cambiar tema">
				<i class="bi bi-moon-fill" id="themeIcon"></i>
			</button>
		</div>
	</aside>
	<div class="lr-sidebar-backdrop" id="lrSidebarBackdrop"></div>

	<!-- Main content -->
	<div class="lr-main">
		<header class="lr-topbar">
			<button class="btn btn-sm btn-outline-secondary lr-sidebar-mobile-btn" id="lrSidebarToggle" type="button">
				<i class="bi bi-list"></i>
			</button>
			<span class="text-muted small d-none d-md-block">{{ $appName ?? 'Los Robles' }} — Administración de Condominios</span>
			<div class="ms-auto d-flex align-items-center gap-2">
				@php $unreadCount = \App\Models\Notification::where('user_id', auth()->id())->where('read', false)->count(); @endphp
				<div class="dropdown">
					<button class="btn btn-sm btn-outline-secondary position-relative" type="button" data-bs-toggle="dropdown" title="Notificaciones">
						<i class="bi bi-bell"></i>
						@if($unreadCount > 0)
							<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
						@endif
					</button>
					<ul class="dropdown-menu dropdown-menu-end" style="min-width:320px;max-height:400px;overflow-y:auto">
						<li class="px-3 py-2 d-flex justify-content-between align-items-center border-bottom">
							<span class="fw-semibold small">Notificaciones</span>
							@if($unreadCount > 0)
								<form method="POST" action="{{ route('notifications.readAll') }}">@csrf
									<button class="btn btn-sm btn-link p-0 text-decoration-none small">Marcar todas leídas</button>
								</form>
							@endif
						</li>
						@php $recentNotifs = \App\Models\Notification::where('user_id', auth()->id())->orderByDesc('created_at')->limit(8)->get(); @endphp
						@foreach($recentNotifs as $n)
							<li>
								<a class="dropdown-item py-2 {{ $n->read ? '' : 'fw-semibold bg-light' }}" href="{{ $n->url ?: '#' }}">
									<div class="d-flex gap-2">
										<i class="bi {{ $n->iconClass() }} mt-1"></i>
										<div class="flex-grow-1">
											<div class="small">{{ $n->title }}</div>
											@if($n->body)
												<div class="text-muted" style="font-size:0.75rem">{{ $n->body }}</div>
											@endif
											<div class="text-muted" style="font-size:0.7rem">{{ $n->created_at->diffForHumans() }}</div>
										</div>
									</div>
								</a>
							</li>
						@endforeach
						<li class="border-top">
							<a class="dropdown-item text-center small text-decoration-none" href="{{ route('notifications.index') }}">Ver todas</a>
						</li>
					</ul>
				</div>
				<button class="theme-toggle d-md-none" id="themeToggleMobile" type="button" title="Cambiar tema">
					<i class="bi bi-moon-fill" id="themeIconMobile"></i>
				</button>
			</div>
		</header>

		<main class="py-4 flex-grow-1">
			<div class="container-fluid px-4">
				@if(session('status'))
					<div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
						<i class="bi bi-check-circle-fill"></i>
						<div>{{ session('status') }}</div>
					</div>
				@endif
				@if($errors->any())
					<div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
						<i class="bi bi-exclamation-circle-fill"></i>
						<div>{{ $errors->first() }}</div>
					</div>
				@endif
				@yield('content')
			</div>
		</main>

		<footer class="border-top py-3 mt-auto">
			<div class="container-fluid px-4">
				<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 text-muted small">
					<span>&copy; {{ date('Y') }} {{ $appName ?? 'Los Robles' }}. Todos los derechos reservados.</span>
					<nav class="nav gap-2">
						<a class="nav-link p-0 text-muted" href="{{ route('legal.terms') }}">Términos</a>
						<a class="nav-link p-0 text-muted" href="{{ route('legal.privacy') }}">Privacidad</a>
						<a class="nav-link p-0 text-muted" href="{{ route('legal.security') }}">Seguridad</a>
						<a class="nav-link p-0 text-muted" href="{{ route('legal.cookies') }}">Cookies</a>
						<a class="nav-link p-0 text-muted" href="{{ route('legal.retention') }}">Retención</a>
					</nav>
				</div>
			</div>
		</footer>
	</div>
</div>
@else
<main class="py-4 flex-grow-1">
	<div class="container">
		@yield('content')
	</div>
</main>
@endif

{{-- Banner de cookies --}}
<div id="cookieBanner" class="position-fixed bottom-0 start-0 end-0 p-3" style="display:none; z-index:1060;">
	<div class="container">
		<div class="card shadow">
			<div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2 p-3">
				<div class="flex-grow-1 small">
					<i class="bi bi-cookie me-1"></i>
					Usamos cookies técnicas (necesarias) y analíticas (opcionales).
					<a href="{{ route('legal.cookies') }}" class="text-decoration-none">Ver política de cookies</a>.
				</div>
				<div class="d-flex gap-2">
					<button class="btn btn-sm btn-outline-secondary" id="rejectCookies">Solo técnicas</button>
					<button class="btn btn-sm btn-primary" id="acceptCookies">Aceptar todas</button>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
(function(){
	var banner = document.getElementById('cookieBanner');
	if(!banner) return;
	var pref = localStorage.getItem('lr-analytics');
	if(pref === null){
		banner.style.display = 'block';
	}
	document.getElementById('acceptCookies').addEventListener('click', function(){
		localStorage.setItem('lr-analytics', '1');
		banner.style.display = 'none';
	});
	document.getElementById('rejectCookies').addEventListener('click', function(){
		localStorage.setItem('lr-analytics', '0');
		banner.style.display = 'none';
	});
})();
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
	const html = document.documentElement;
	const stored = localStorage.getItem('lr-theme');
	if(stored) html.setAttribute('data-bs-theme', stored);
	function updateIcon(){
		const dark = html.getAttribute('data-bs-theme') === 'dark';
		document.querySelectorAll('#themeIcon, #themeIconMobile').forEach(function(el){
			if(el) el.className = dark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
		});
	}
	updateIcon();
	document.querySelectorAll('#themeToggle, #themeToggleMobile').forEach(function(toggle){
		if(!toggle) return;
		toggle.addEventListener('click', function(){
			const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
			html.setAttribute('data-bs-theme', next);
			localStorage.setItem('lr-theme', next);
			updateIcon();
		});
	});
})();
</script>
<script>
/* Sidebar mobile toggle */
(function(){
	var sidebar = document.getElementById('lrSidebar');
	var backdrop = document.getElementById('lrSidebarBackdrop');
	var toggleBtn = document.getElementById('lrSidebarToggle');
	if(!sidebar || !toggleBtn) return;
	toggleBtn.addEventListener('click', function(){
		sidebar.classList.toggle('show');
		if(backdrop) backdrop.classList.toggle('show');
	});
	if(backdrop){
		backdrop.addEventListener('click', function(){
			sidebar.classList.remove('show');
			backdrop.classList.remove('show');
		});
	}
})();
</script>
<script>
/* Prevent double-submit on all forms */
(function(){
	document.addEventListener('submit', function(e){
		var form = e.target;
		if(form.tagName !== 'FORM') return;
		if(form.dataset.submitted === '1'){ e.preventDefault(); return; }
		form.dataset.submitted = '1';
		var btns = form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])');
		btns.forEach(function(btn){ btn.disabled = true; btn.style.opacity = '0.6'; });
		setTimeout(function(){ form.dataset.submitted = ''; btns.forEach(function(btn){ btn.disabled = false; btn.style.opacity = ''; }); }, 5000);
	}, true);
})();
</script>
@php // Chat IA ocultado temporalmente - se ve mal. Reactivar cuando se mejore el diseño.
   // @include('chatbot.widget')
@endphp
@stack('scripts')
</body>
</html>
