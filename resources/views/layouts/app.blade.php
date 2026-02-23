<!DOCTYPE html>
<html>
<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>LOS ROBLES</title>
		<!-- Bootstrap 5 CDN -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-" crossorigin="anonymous">
</head>
<body>
@if (!Request::is('login'))
<nav class="navbar navbar-expand-lg navbar-light bg-light">
	<div class="container-fluid">
		<a class="navbar-brand" href="/">Los Robles</a>
		<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarNav">
			<ul class="navbar-nav">
				<li class="nav-item"><a class="nav-link" href="{{ route('users.index') }}">Usuarios</a></li>
				{{-- En multi-tenant la gestión de condominios no se usa desde el tenant; opcional ocultarlo --}}
				<li class="nav-item"><a class="nav-link" href="{{ route('towers.index') }}">Torres</a></li>
				<li class="nav-item">
					<a class="nav-link" href="#" onclick="event.preventDefault(); alert('Seleccione una torre en el listado para ver sus apartamentos.');">Apartamentos</a>
				</li>
				<li class="nav-item"><a class="nav-link" href="{{ route('expense-items.index') }}">Gastos</a></li>
				<li class="nav-item"><a class="nav-link" href="{{ route('invoices.index') }}">Facturas</a></li>
				<li class="nav-item"><a class="nav-link" href="{{ route('rates.index') }}">Tasas</a></li>
				<li class="nav-item"><a class="nav-link" href="{{ route('accounts.index') }}">Cuentas</a></li>
				<li class="nav-item"><a class="nav-link" href="{{ route('exchange.create') }}">Cambio</a></li>
			</ul>
		</div>
	</div>
</nav>
@endif

<section class="py-4">
	<div class="container">
		@yield('content')
	</div>
</section>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-" crossorigin="anonymous"></script>
@stack('scripts')
</body>
</html>
