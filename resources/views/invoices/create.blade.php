@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
	<h1><i class="bi bi-receipt me-2"></i>Nueva Factura</h1>
	<a class="btn btn-outline-secondary btn-action" href="{{route('invoices.index')}}"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
	<form method="GET" action="{{route('invoices.create')}}" class="row g-3 mb-4 align-items-end">
		@if(auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('condo_admin'))
		<div class="col-md-4">
			<label class="form-label">Torre (opcional)</label>
			<select name="tower_id" class="form-select" onchange="this.form.submit()">
				<option value="">-- Todas --</option>
				@foreach($towers as $t)
					<option value="{{$t->id}}" @if($selectedTower && $selectedTower->id==$t->id) selected @endif>{{$t->name}}</option>
				@endforeach
			</select>
		</div>
		@endif
	</form>
	<form method="POST" action="{{route('invoices.store')}}" class="card">
		<div class="card-body">
		@csrf
		@php($currentRate = (float)($activeRate->rate ?? 0))
		@if($selectedTower)<input type="hidden" name="tower_id" value="{{$selectedTower->id}}">@endif
		<div class="mb-3">
			<label class="form-label">Periodo</label>
			<input type="hidden" name="period" id="periodValue" value="{{ old('period', date('Y-m')) }}" required>
			@php
				$meses = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
				$curPeriod = old('period', date('Y-m'));
				$curYear = (int) substr($curPeriod, 0, 4);
				$curMonth = substr($curPeriod, 5, 2);
			@endphp
			<div class="d-flex gap-2" style="max-width:280px;">
				<select id="periodMonth" class="form-select" onchange="syncPeriod()">
					@foreach($meses as $num => $nombre)
						<option value="{{ $num }}" @if($curMonth === $num) selected @endif>{{ $nombre }}</option>
					@endforeach
				</select>
				<select id="periodYear" class="form-select" onchange="syncPeriod()">
					@for($y = $curYear - 2; $y <= $curYear + 2; $y++)
						<option value="{{ $y }}" @if($y === $curYear) selected @endif>{{ $y }}</option>
					@endfor
				</select>
			</div>
		</div>
		<div class="mb-3">
			@php $towerMap = $towers->pluck('name','id'); @endphp
			<div class="d-flex justify-content-between align-items-center mb-2">
				<label class="form-label m-0">Apartamentos</label>
				<span class="text-muted small">Seleccionados: <strong id="aptSelectedCount">0</strong> / {{ $apartments->count() }}</span>
			</div>
			<div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
				<div class="input-group input-group-sm" style="max-width:220px;">
					<span class="input-group-text"><i class="bi bi-search"></i></span>
					<input type="text" id="aptSearch" class="form-control" placeholder="Buscar código...">
				</div>
				<select id="aptTowerFilter" class="form-select form-select-sm" style="max-width:180px;">
					<option value="">Todas las torres</option>
					@foreach($towers as $t)
						<option value="{{ $t->id }}">{{ $t->name }}</option>
					@endforeach
				</select>
				<button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectVisible"><i class="bi bi-check-all"></i> Seleccionar visibles</button>
				<button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearVisible"><i class="bi bi-x-lg"></i> Limpiar visibles</button>
			</div>
			<div id="apartmentsList" class="border rounded p-2 d-flex flex-wrap gap-1" style="max-height:240px; overflow-y:auto;">
				@foreach($apartments as $ap)
					<div class="apartment-row" data-tower-id="{{ $ap->tower_id }}" data-code="{{ strtolower($ap->code) }}" style="width:auto;">
						<input class="btn-check apt-check" type="checkbox" name="apartment_ids[]" value="{{ $ap->id }}" id="ap{{ $ap->id }}" autocomplete="off">
						<label class="btn btn-outline-secondary btn-sm py-1 px-2" for="ap{{ $ap->id }}" title="{{ $towerMap[$ap->tower_id] ?? '' }} — {{ number_format($ap->aliquot_percent, 2) }}%">
							{{ $ap->code }}
						</label>
					</div>
				@endforeach
			</div>
			<small class="text-muted">Requerido solo si agregas ítems de gasto</small>
		</div>
		<div class="mb-3">
			<div class="d-flex justify-content-between align-items-center">
				<label class="form-label m-0">Agregar gastos a esta factura</label>
				<span class="text-muted small">Tasa activa: <strong id="rateLabel">{{ number_format($currentRate, 2) }}</strong> VES/USD</span>
				<div class="d-flex gap-2">
					<input type="text" id="expenseSearch" class="form-control" placeholder="Buscar gasto..." oninput="filterExpenseOptions()">
					<select id="expenseSelect" class="form-select">
						<option value="">-- Selecciona un gasto --</option>
						@foreach($items as $it)
							@php($t = $it->type ?? 'fixed')
							<option value="{{$it->id}}" data-name="{{$it->name}}" data-type="{{$t}}">{{$it->name}}</option>
						@endforeach
					</select>
					<button type="button" class="btn btn-outline-primary" onclick="addExpenseRow()">Agregar</button>
					<button type="button" class="btn btn-outline-secondary" onclick="openNewExpenseModal()">Nuevo gasto</button>
				</div>
			</div>
			<div class="table-responsive mt-3">
				<table class="table table-striped table-bordered" id="invoiceItemsTable">
					<thead class="table-light">
						<tr>
							<th>Gasto</th>
							<th>Monto USD</th>
							<th>Monto VES</th>
							<th>Cantidad</th>
							<th>Distribución</th>
							<th></th>
						</tr>
					</thead>
										<tbody></tbody>
										<tfoot>
																	<tr>
																		<th colspan="5" class="text-end">
																			Total global estimado: <strong id="estimatedTotal">0.00</strong> USD
																			<span class="ms-3 text-muted">
																				(Alícuota: <span id="estimatedAliquota">0.00</span> | Igual c/apto: <span id="estimatedEqual">0.00</span>)
																			</span>
																		</th>
																	</tr>
																	<tr>
																		<th colspan="5" class="text-end text-muted fw-normal small">
																			Promedio por apartamento: <strong id="estimatedPerApt">0.00</strong> USD
																			<span class="ms-2">(sobre <span id="selectedAptCount">0</span> seleccionados)</span>
																		</th>
																	</tr>
										</tfoot>
				</table>
			</div>
			<input type="hidden" name="items_payload" id="itemsPayload">
		</div>
		<div class="row">
			<div class="col-md-4 mb-3">
				<label class="form-label">Mora Tipo</label>
				<select name="late_fee_type" class="form-select">
					<option value="">(ninguno)</option>
					<option value="percent">Porcentaje</option>
					<option value="fixed">Monto Fijo</option>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Mora Alcance</label>
				<select name="late_fee_scope" class="form-select">
					<option value="">(ninguno)</option>
					<option value="day">Día</option>
					<option value="week">Semana</option>
					<option value="month">Mes</option>
				</select>
			</div>
			<div class="col-md-4 mb-3">
				<label class="form-label">Valor Mora</label>
				<input type="number" step="0.01" name="late_fee_value" class="form-control" value="0">
			</div>
		</div>
		<button class="btn btn-primary btn-action"><i class="bi bi-check-lg"></i> Guardar borrador</button>
		</div>
	</form>

<!-- Modal: nuevo gasto del catálogo (sin salir de Factura) -->
<div class="modal fade" id="newExpenseModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Nuevo gasto</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label">Nombre</label>
					<input type="text" class="form-control" id="newExpenseName" maxlength="120" placeholder="Ej: Limpieza" />
				</div>
				<div class="mb-3">
					<label class="form-label">Tipo</label>
					<select class="form-select" id="newExpenseType">
						<option value="fixed">Fijo</option>
						<option value="aliquot">Alícuota</option>
					</select>
				</div>
				<div class="alert alert-danger d-none" id="newExpenseError"></div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary" onclick="submitNewExpense()">Crear</button>
			</div>
		</div>
	</div>
</div>

@push('scripts')
<script>
function syncPeriod(){
	const m = document.getElementById('periodMonth').value;
	const y = document.getElementById('periodYear').value;
	document.getElementById('periodValue').value = y + '-' + m;
}
const activeRate = {{ $currentRate > 0 ? $currentRate : 0 }};
const items = new Map();
function markAddedExpenses(){
	const select = document.getElementById('expenseSelect');
	Array.from(select.options).forEach(opt => {
		if(!opt.value) return;
		const added = items.has(opt.value);
		opt.textContent = (added ? '\u2713 ' : '') + opt.dataset.name;
		opt.style.color = added ? '#6c757d' : '';
	});
}
function addExpenseRow(){
	const select = document.getElementById('expenseSelect');
	const id = select.value; if(!id) return;
	const name = select.selectedOptions[0].dataset.name;
	if(items.has(id)) { alert('Este gasto ya fue agregado.'); return; }
	items.set(id, { expense_item_id: id, amount: 0, amount_ves: 0, quantity: 1, distribution: 'aliquota' });
	renderItems();
}
function removeExpenseRow(id){ items.delete(String(id)); renderItems(); }
function updateField(id, field, value){
	const it = items.get(String(id)); if(!it) return;
	if(field === 'amount'){
		value = parseFloat(value || 0);
		it.amount = value;
		it.amount_ves = activeRate > 0 ? (value * activeRate) : 0;
		items.set(String(id), it);
		renderItems();
		return;
	}
	if(field === 'amount_ves'){
		value = parseFloat(value || 0);
		it.amount_ves = value;
		it.amount = activeRate > 0 ? (value / activeRate) : 0;
		items.set(String(id), it);
		renderItems();
		return;
	}
	if(field === 'quantity'){ value = parseFloat(value || 0); }
	it[field] = value; items.set(String(id), it); syncPayload();
}
function renderItems(){
	const tbody = document.querySelector('#invoiceItemsTable tbody');
	tbody.innerHTML = '';
	for(const [id, it] of items.entries()){
		const tr = document.createElement('tr');
		tr.innerHTML = `
			<td>${document.querySelector(`#expenseSelect option[value='${id}']`).dataset.name}</td>
			<td><input type="number" step="0.01" class="form-control form-control-sm" value="${it.amount}" onchange="updateField('${id}','amount',this.value)"></td>
			<td><input type="number" step="0.01" class="form-control form-control-sm" value="${(it.amount_ves ?? ((it.amount||0)*activeRate)).toFixed(2)}" onchange="updateField('${id}','amount_ves',this.value)"></td>
			<td><input type="number" step="1" class="form-control form-control-sm" value="${it.quantity}" onchange="updateField('${id}','quantity',this.value)"></td>
			<td>
				<select class="form-select form-select-sm" onchange="updateField('${id}','distribution',this.value)">
					<option value="aliquota" ${it.distribution==='aliquota'?'selected':''}>Por alícuota</option>
					<option value="equal" ${it.distribution==='equal'?'selected':''}>Igual para todos</option>
				</select>
			</td>
			<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExpenseRow('${id}')">Quitar</button></td>
		`;
		tbody.appendChild(tr);
	}
	markAddedExpenses();
	syncPayload();
}
function syncPayload(){
	const arr = Array.from(items.values()).map(i => ({
		expense_item_id: i.expense_item_id,
		amount: parseFloat(i.amount || 0),
		quantity: parseInt(i.quantity || 1),
		distribution: i.distribution || 'aliquota',
	}));
	document.getElementById('itemsPayload').value = JSON.stringify(arr);
	const aptCount = document.querySelectorAll('.apt-check:checked').length;
	// compute estimated total
	let total = 0;
	let totalAliquota = 0;
	let totalEqual = 0;
	arr.forEach(it => {
		const amt = parseFloat(it.amount || 0);
		const qty = parseInt(it.quantity || 1);
		const t = (amt * qty);
		if(it.distribution === 'aliquota'){
			// Alícuota: el monto se reparte entre apartamentos
			totalAliquota += t;
			total += t;
		} else {
			// Igual: cada apartamento paga el monto completo
			totalEqual += t;
			total += t * aptCount;
		}
	});
	document.getElementById('estimatedTotal').innerText = total.toFixed(2);
	document.getElementById('estimatedAliquota').innerText = totalAliquota.toFixed(2);
	document.getElementById('estimatedEqual').innerText = totalEqual.toFixed(2) + (aptCount > 0 ? ' x' + aptCount + ' = ' + (totalEqual * aptCount).toFixed(2) : '');
	document.getElementById('selectedAptCount').innerText = aptCount;
	document.getElementById('estimatedPerApt').innerText = aptCount > 0 ? (total / aptCount).toFixed(2) : '0.00';
}

function filterExpenseOptions(){
	const term = (document.getElementById('expenseSearch').value || '').toLowerCase();
	const select = document.getElementById('expenseSelect');
	Array.from(select.options).forEach(opt => {
		if(!opt.value) return; // leave placeholder
		const name = (opt.dataset.name || '').toLowerCase();
		opt.hidden = term && !name.includes(term);
	});
}
// Apartments: checkbox filter, search, select/clear
(function(){
	const searchInput = document.getElementById('aptSearch');
	const towerFilter = document.getElementById('aptTowerFilter');
	const rows = document.querySelectorAll('.apartment-row');
	const countEl = document.getElementById('aptSelectedCount');

	function filterRows(){
		const term = (searchInput.value || '').toLowerCase();
		const tower = towerFilter.value;
		rows.forEach(row => {
			const matchCode = !term || row.dataset.code.includes(term);
			const matchTower = !tower || row.dataset.towerId === tower;
			row.style.display = (matchCode && matchTower) ? '' : 'none';
		});
	}
	function updateCount(){
		const checked = document.querySelectorAll('.apt-check:checked').length;
		countEl.textContent = checked;
		syncPayload(); // recalculate per-apartment estimate
	}
	searchInput.addEventListener('input', filterRows);
	towerFilter.addEventListener('change', filterRows);
	document.getElementById('btnSelectVisible').addEventListener('click', () => {
		rows.forEach(row => {
			if(row.style.display !== 'none'){
				row.querySelector('.apt-check').checked = true;
			}
		});
		updateCount();
	});
	document.getElementById('btnClearVisible').addEventListener('click', () => {
		rows.forEach(row => {
			if(row.style.display !== 'none'){
				row.querySelector('.apt-check').checked = false;
			}
		});
		updateCount();
	});
	document.querySelectorAll('.apt-check').forEach(cb => cb.addEventListener('change', updateCount));
	updateCount();
	syncPayload();
})();

let newExpenseModalInstance = null;
function openNewExpenseModal(){
	const err = document.getElementById('newExpenseError');
	err.classList.add('d-none');
	err.innerText = '';
	document.getElementById('newExpenseName').value = '';
	document.getElementById('newExpenseType').value = 'fixed';
	newExpenseModalInstance = new bootstrap.Modal(document.getElementById('newExpenseModal'));
	newExpenseModalInstance.show();
}

async function submitNewExpense(){
	const name = (document.getElementById('newExpenseName').value || '').trim();
	const type = document.getElementById('newExpenseType').value;
	const err = document.getElementById('newExpenseError');
	err.classList.add('d-none');
	err.innerText = '';
	if(!name){
		err.innerText = 'El nombre es requerido.';
		err.classList.remove('d-none');
		return;
	}
	try{
		const res = await fetch("{{ route('expense-items.inlineStore') }}", {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': "{{ csrf_token() }}",
				'Accept': 'application/json',
			},
			body: JSON.stringify({ name, type, active: true }),
		});
		if(!res.ok){
			let msg = 'No se pudo crear el gasto.';
			try{
				const payload = await res.json();
				if(payload?.message) msg = payload.message;
				if(payload?.errors){
					msg = Object.values(payload.errors).flat().join(' ');
				}
			}catch(e){}
			throw new Error(msg);
		}
		const payload = await res.json();
		const select = document.getElementById('expenseSelect');
		const opt = document.createElement('option');
		opt.value = String(payload.id);
		opt.textContent = payload.name;
		opt.dataset.name = payload.name;
		opt.dataset.type = payload.type || 'fixed';
		select.appendChild(opt);
		select.value = String(payload.id);
		if(newExpenseModalInstance){ newExpenseModalInstance.hide(); }
	}catch(e){
		err.innerText = e?.message || 'Error.';
		err.classList.remove('d-none');
	}
}
</script>
@endpush
<!-- Se elimina formulario duplicado legacy -->
@endsection
