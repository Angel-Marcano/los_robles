@extends('layouts.app')
@section('content')
<div class="container">
	<h1 class="mb-4">Nueva Factura</h1>
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
	<form method="POST" action="{{route('invoices.store')}}" class="card p-4 shadow-sm">
		@csrf
		@if($selectedTower)<input type="hidden" name="tower_id" value="{{$selectedTower->id}}">@endif
		<div class="mb-3">
			<label class="form-label">Periodo (YYYY-MM)</label>
			<input name="period" class="form-control" placeholder="2025-11" required>
		</div>
		<div class="mb-3">
			<div class="d-flex justify-content-between align-items-center">
				<label class="form-label m-0">Apartamentos</label>
				<div class="text-muted">Seleccionados: <strong id="aptSelectedCount">0</strong></div>
			</div>
			<div class="d-flex gap-2 mb-2">
				<input type="text" id="aptSearch" class="form-control" placeholder="Buscar por código..." oninput="filterApartmentOptions()">
				<button type="button" class="btn btn-outline-secondary" onclick="clearApartmentFilter()">Limpiar</button>
			</div>
			<select name="apartment_ids[]" id="apartmentSelect" class="form-select" multiple onchange="updateAptSelectedCount()">
				@foreach($apartments as $ap)
					<option value="{{$ap->id}}" data-code="{{$ap->code}}">{{$ap->code}}</option>
				@endforeach
			</select>
			<small class="text-muted">Puedes buscar y seleccionar varios apartamentos. (Requerido solo si agregas ítems)</small>
		</div>
		<div class="mb-3">
			<div class="d-flex justify-content-between align-items-center">
				<label class="form-label m-0">Agregar gastos a esta factura</label>
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
							<th>Cantidad</th>
							<th>Distribución</th>
							<th></th>
						</tr>
					</thead>
										<tbody></tbody>
										<tfoot>
																	<tr>
																		<th colspan="5" class="text-end">
																			Total estimado: <strong id="estimatedTotal">0.00</strong> USD
																			<span class="ms-3 text-muted">
																				(Alícuota: <span id="estimatedAliquota">0.00</span> | Igual: <span id="estimatedEqual">0.00</span>)
																			</span>
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
		<button class="btn btn-primary">Guardar borrador</button>
	</form>
</div>

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
const items = new Map();
function addExpenseRow(){
	const select = document.getElementById('expenseSelect');
	const id = select.value; if(!id) return;
	const name = select.selectedOptions[0].dataset.name;
	if(items.has(id)) { alert('Este gasto ya fue agregado.'); return; }
	items.set(id, { expense_item_id: id, amount: 0, quantity: 1, distribution: 'aliquota' });
	renderItems();
}
function removeExpenseRow(id){ items.delete(String(id)); renderItems(); }
function updateField(id, field, value){
	const it = items.get(String(id)); if(!it) return;
	if(field === 'amount' || field === 'quantity'){ value = parseFloat(value || 0); }
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
	syncPayload();
}
function syncPayload(){
	const arr = Array.from(items.values());
	document.getElementById('itemsPayload').value = JSON.stringify(arr);
	// compute estimated total
	let total = 0;
	let totalAliquota = 0;
	let totalEqual = 0;
	arr.forEach(it => {
		// estimation assumes equal distribution totals; for aliquota, total es amount*quantity
		const amt = parseFloat(it.amount || 0);
		const qty = parseInt(it.quantity || 1);
		const t = (amt * qty);
		total += t;
		if(it.distribution === 'aliquota') totalAliquota += t; else totalEqual += t;
	});
	document.getElementById('estimatedTotal').innerText = total.toFixed(2);
	document.getElementById('estimatedAliquota').innerText = totalAliquota.toFixed(2);
	document.getElementById('estimatedEqual').innerText = totalEqual.toFixed(2);
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
// Apartments search & counter
function filterApartmentOptions(){
	const term = (document.getElementById('aptSearch').value || '').toLowerCase();
	const select = document.getElementById('apartmentSelect');
	Array.from(select.options).forEach(opt => {
		const code = (opt.dataset.code || '').toLowerCase();
		opt.hidden = term && !code.includes(term);
	});
}
function clearApartmentFilter(){
	document.getElementById('aptSearch').value = '';
	filterApartmentOptions();
}
function updateAptSelectedCount(){
	const select = document.getElementById('apartmentSelect');
	const count = Array.from(select.selectedOptions).length;
	document.getElementById('aptSelectedCount').innerText = count;
}
// initialize count on load
document.addEventListener('DOMContentLoaded', () => {
	updateAptSelectedCount();
	// ensure payload always exists even if no rows
	syncPayload();
});

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
