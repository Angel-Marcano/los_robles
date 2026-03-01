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

		/* ── Navbar ── */
		.lr-navbar {
			backdrop-filter: blur(10px);
			-webkit-backdrop-filter: blur(10px);
			border-bottom: 1px solid rgba(0,0,0,.08);
		}
		[data-bs-theme="dark"] .lr-navbar { border-bottom-color: rgba(255,255,255,.06); }
		.lr-navbar .nav-link { font-size: .875rem; font-weight: 500; padding: .5rem .85rem !important; border-radius: .5rem; transition: background .2s, color .2s; }
		.lr-navbar .nav-link:hover { background: rgba(var(--bs-primary-rgb),.1); }
		.lr-navbar .nav-link.active { background: rgba(var(--bs-primary-rgb),.15); color: var(--bs-primary) !important; }
		.navbar-brand { font-weight: 700; letter-spacing: -.5px; font-size: 1.15rem; }
		.navbar-brand i { color: var(--bs-success); }

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
			.lr-navbar, .theme-toggle, .no-print { display: none !important; }
			.card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
		}
	</style>
	@stack('styles')
</head>
<body>
@if (!Request::is('login'))
<nav class="navbar navbar-expand-lg sticky-top bg-body lr-navbar">
	<div class="container">
		<a class="navbar-brand d-flex align-items-center gap-2" href="/">
			<i class="bi bi-buildings"></i> {{ $appName ?? 'Los Robles' }}
		</a>
		<div class="d-flex align-items-center gap-2 order-lg-last">
			<button class="theme-toggle" id="themeToggle" type="button" title="Cambiar tema">
				<i class="bi bi-moon-fill" id="themeIcon"></i>
			</button>
			@auth
			<div class="dropdown">
				<button class="btn btn-sm btn-outline-secondary btn-action dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
					<i class="bi bi-person-circle"></i>
					<span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
				</button>
				<ul class="dropdown-menu dropdown-menu-end">
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
			@endauth
		</div>
		<button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarNav">
			<ul class="navbar-nav me-auto gap-1">
				@php $isAdmin = auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('condo_admin') || auth()->user()->hasRole('tower_admin')); @endphp
				@if($isAdmin)
				<li class="nav-item">
					<a class="nav-link {{ Request::routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
						<i class="bi bi-people me-1"></i>Usuarios
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link {{ Request::routeIs('towers.*') || Request::routeIs('towers.apartments.*') ? 'active' : '' }}" href="{{ route('towers.index') }}">
						<i class="bi bi-building me-1"></i>Torres
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link {{ Request::routeIs('expense-items.*') ? 'active' : '' }}" href="{{ route('expense-items.index') }}">
						<i class="bi bi-receipt me-1"></i>Gastos
					</a>
				</li>
				@endif
				<li class="nav-item">
					<a class="nav-link {{ Request::routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
						<i class="bi bi-file-earmark-text me-1"></i>Facturas
					</a>
				</li>
				@if($isAdmin)
				<li class="nav-item">
					<a class="nav-link {{ Request::routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.debtorsMonthly') }}">
						<i class="bi bi-exclamation-triangle me-1"></i>Deudores
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link {{ Request::routeIs('rates.*') ? 'active' : '' }}" href="{{ route('rates.index') }}">
						<i class="bi bi-currency-exchange me-1"></i>Tasas
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link {{ Request::routeIs('accounts.*') ? 'active' : '' }}" href="{{ route('accounts.index') }}">
						<i class="bi bi-wallet2 me-1"></i>Cuentas
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link {{ Request::routeIs('exchange.*') ? 'active' : '' }}" href="{{ route('exchange.create') }}">
						<i class="bi bi-arrow-left-right me-1"></i>Cambio
					</a>
				</li>
				@endif
			</ul>
		</div>
	</div>
</nav>
@endif

<main class="py-4">
	<div class="container">
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
	const html = document.documentElement;
	const toggle = document.getElementById('themeToggle');
	const icon = document.getElementById('themeIcon');
	const stored = localStorage.getItem('lr-theme');
	if(stored) html.setAttribute('data-bs-theme', stored);
	function updateIcon(){
		const dark = html.getAttribute('data-bs-theme') === 'dark';
		if(icon){
			icon.className = dark ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
		}
	}
	updateIcon();
	if(toggle){
		toggle.addEventListener('click', function(){
			const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
			html.setAttribute('data-bs-theme', next);
			localStorage.setItem('lr-theme', next);
			updateIcon();
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
@stack('scripts')
</body>
</html>
