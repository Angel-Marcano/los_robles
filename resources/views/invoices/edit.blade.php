@extends('layouts.app')
@section('content')
  <div class="d-flex justify-content-between align-items-center page-header">
    <h1><i class="bi bi-pencil-square me-2"></i>Editar Factura #{{ $invoice->id }}</h1>
    <a class="btn btn-outline-secondary btn-action" href="{{ route('invoices.show',$invoice) }}"><i class="bi bi-arrow-left"></i> Volver</a>
  </div>
  @if($invoice->status!=='draft')
    <div class="alert alert-warning">Solo se puede editar cuando la factura está en borrador.</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('invoices.update',$invoice) }}" id="invoice-edit-form">
    @csrf @method('PATCH')

    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Periodo</label>
        <input type="hidden" name="period" id="periodValue" value="{{ old('period', $invoice->period) }}" required>
        @php
          $meses = ['01'=>'Ene','02'=>'Feb','03'=>'Mar','04'=>'Abr','05'=>'May','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Dic'];
          $curPeriod = old('period', $invoice->period);
          $curYear = (int) substr($curPeriod, 0, 4);
          $curMonth = substr($curPeriod, 5, 2);
        @endphp
        <div class="d-flex gap-2">
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
      <div class="col-md-3">
        <label class="form-label">Torre</label>
        <select name="tower_id" class="form-select">
          <option value="">Todas</option>
          @foreach($towers as $t)
            <option value="{{ $t->id }}" @if(optional($selectedTower)->id===$t->id) selected @endif>{{ $t->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Mora tipo</label>
        <select name="late_fee_type" class="form-select">
          <option value="">Ninguna</option>
          <option value="percent" @if(old('late_fee_type',$invoice->late_fee_type)==='percent') selected @endif>Porcentaje</option>
          <option value="fixed" @if(old('late_fee_type',$invoice->late_fee_type)==='fixed') selected @endif>Fijo</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Mora alcance</label>
        <select name="late_fee_scope" class="form-select">
          <option value="">--</option>
          <option value="day" @if(old('late_fee_scope',$invoice->late_fee_scope)==='day') selected @endif>Por día</option>
          <option value="week" @if(old('late_fee_scope',$invoice->late_fee_scope)==='week') selected @endif>Por semana</option>
          <option value="month" @if(old('late_fee_scope',$invoice->late_fee_scope)==='month') selected @endif>Por mes</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Mora valor</label>
        <input type="number" step="0.01" name="late_fee_value" class="form-control" value="{{ old('late_fee_value',$invoice->late_fee_value) }}">
      </div>
    </div>

    <hr class="my-3">

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Apartamentos</label>
        @php $towerMap = $towers->pluck('name','id'); @endphp
        <div class="d-flex gap-2 align-items-center mb-2">
          <div class="input-group input-group-sm" style="max-width: 260px;">
            <span class="input-group-text">Buscar</span>
            <input type="text" id="apt-global-filter" class="form-control" placeholder="Código o texto...">
          </div>
          <select id="apt-global-tower" class="form-select form-select-sm" style="max-width:200px;">
            <option value="">Todas las torres</option>
            @foreach($towers as $t)
              <option value="{{ $t->id }}">{{ $t->name }}</option>
            @endforeach
          </select>
          <button type="button" class="btn btn-sm btn-outline-primary" id="apt-global-select-visible">Seleccionar visibles</button>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="apt-global-clear-visible">Limpiar visibles</button>
        </div>
        <div id="global-apartments-list" class="border rounded p-2 d-flex flex-wrap gap-1" style="max-height:260px; overflow:auto">
          @foreach($apartments as $ap)
            <div class="apartment-row" data-tower-id="{{ $ap->tower_id }}" data-tower-name="{{ $towerMap[$ap->tower_id] ?? '' }}" data-code="{{ \Illuminate\Support\Str::lower($ap->code) }}" style="width:auto;">
              <input class="btn-check" type="checkbox" name="apartment_ids[]" value="{{ $ap->id }}" id="ap{{ $ap->id }}" @if(collect($selectedApartmentIds??[])->contains($ap->id)) checked @endif autocomplete="off">
              <label class="btn btn-outline-secondary btn-sm py-1 px-2" for="ap{{ $ap->id }}" title="{{ $towerMap[$ap->tower_id] ?? '-' }} — {{ $ap->aliquot_percent }}%">{{ $ap->code }}</label>
            </div>
          @endforeach
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Gastos del catálogo</label>
        <div class="d-flex justify-content-end mb-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openNewExpenseModal()">Nuevo gasto</button>
        </div>
        <div class="border rounded p-2" id="expense-catalog" style="max-height:260px; overflow:auto">
          <div class="input-group input-group-sm mb-2">
            <span class="input-group-text">Buscar</span>
            <input type="text" id="item-filter" class="form-control" placeholder="Filtrar...">
          </div>
          @foreach($items as $it)
          @php($t = $it->type ?? 'fixed')
          <div class="d-flex align-items-center gap-2 mb-1 item-row" data-name="{{ \Illuminate\Support\Str::slug($it->name) }}">
            <span class="flex-grow-1">{{ $it->name }} <span class="text-muted">({{ $t==='aliquot' ? 'Alícuota' : 'Fijo' }})</span></span>
              <button
                type="button"
                class="btn btn-sm btn-outline-primary js-add-item"
                data-expense-id="{{ $it->id }}"
                data-expense-name="{{ addslashes($it->name) }}"
              >Agregar</button>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header">Ítems seleccionados</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped mb-0" id="items-table">
            <thead>
              <tr>
                <th>Gasto</th>
                <th style="width:120px">Monto USD</th>
                <th style="width:100px">Cantidad</th>
                <th style="width:140px">Distribución</th>
                <th style="width:100px">Aptos</th>
                <th style="width:60px"></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>

    <input type="hidden" name="items_payload" id="items_payload">
    <div class="mt-3 d-flex justify-content-end gap-2">
      <a class="btn btn-outline-secondary btn-action" href="{{ route('invoices.show',$invoice) }}"><i class="bi bi-x-lg"></i> Cancelar</a>
      <button type="submit" class="btn btn-primary btn-action" onclick="return beforeSubmit()"><i class="bi bi-check-lg"></i> Guardar cambios</button>
    </div>
  </form>
@endsection

@push('scripts')
<script>
function syncPeriod(){
  const m = document.getElementById('periodMonth').value;
  const y = document.getElementById('periodYear').value;
  document.getElementById('periodValue').value = y + '-' + m;
}
const items = [];

function escapeHtml(str){
  return String(str ?? '')
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;')
    .replace(/'/g,'&#039;');
}

function updateCatalogButtons(){
  document.querySelectorAll('.js-add-item').forEach(btn => {
    const id = parseInt(btn.getAttribute('data-expense-id'));
    const exists = items.some(i => parseInt(i.expense_item_id) === id);

    if(exists){
      btn.disabled = true;
      btn.classList.remove('btn-outline-primary');
      btn.classList.add('btn-outline-secondary');
      btn.textContent = 'Agregado';
    }else{
      btn.disabled = false;
      btn.classList.remove('btn-outline-secondary');
      btn.classList.add('btn-outline-primary');
      btn.textContent = 'Agregar';
    }
  });
}

function wireCatalogButtons(){
  document.querySelectorAll('.js-add-item').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = parseInt(btn.getAttribute('data-expense-id'));
      const name = btn.getAttribute('data-expense-name');
      addItem(id, name);
    });
  });
}

function addItem(id, name){
  const exists = items.find(i => i.expense_item_id === id);
  if(exists){ alert('Ya agregado'); return; }
  items.push({expense_item_id:id, name:name, amount:0, quantity:1, distribution:'aliquota', apartment_ids: []});
  renderItems();
}
function removeItem(id){
  const idx = items.findIndex(i => i.expense_item_id === id);
  if(idx>-1){ items.splice(idx,1); renderItems(); }
}
function renderItems(){
  const tbody = document.querySelector('#items-table tbody');
  tbody.innerHTML = '';
  items.forEach((i, idx) => {
    const tr = document.createElement('tr');
    const countApt = (Array.isArray(i.apartment_ids) ? i.apartment_ids.length : 0);
    tr.innerHTML = `
      <td>${escapeHtml(i.name)}</td>
      <td><input type="number" step="0.01" class="form-control form-control-sm" value="${i.amount}" data-idx="${idx}" data-field="amount"></td>
      <td><input type="number" step="1" min="1" class="form-control form-control-sm" value="${i.quantity}" data-idx="${idx}" data-field="quantity"></td>
      <td>
        <select class="form-select form-select-sm" data-idx="${idx}" data-field="distribution">
          <option value="aliquota" ${i.distribution==='aliquota'?'selected':''}>Aliquota</option>
          <option value="equal" ${i.distribution==='equal'?'selected':''}>Igual</option>
        </select>
      </td>
      <td><button type="button" class="btn btn-sm btn-outline-secondary" data-action="aptos" data-idx="${idx}">Aptos (${countApt})</button></td>
      <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-idx="${idx}">Quitar</button></td>
    `;
    tbody.appendChild(tr);
  });
  // bind events
  tbody.querySelectorAll('input[data-field=amount]').forEach(el=>{
    el.addEventListener('input', (e)=>{
      const idx = parseInt(e.target.getAttribute('data-idx')); items[idx].amount = parseFloat(e.target.value||0);
    });
  });
  tbody.querySelectorAll('input[data-field=quantity]').forEach(el=>{
    el.addEventListener('input', (e)=>{
      const idx = parseInt(e.target.getAttribute('data-idx')); items[idx].quantity = Math.max(1, parseInt(e.target.value||1));
    });
  });
  tbody.querySelectorAll('select[data-field=distribution]').forEach(el=>{
    el.addEventListener('change', (e)=>{
      const idx = parseInt(e.target.getAttribute('data-idx')); items[idx].distribution = e.target.value;
    });
  });
  tbody.querySelectorAll('button.btn-outline-danger').forEach(el=>{
    el.addEventListener('click', (e)=>{
      const idx = parseInt(e.target.getAttribute('data-idx')); items.splice(idx,1); renderItems();
    });
  });
  tbody.querySelectorAll('button[data-action=aptos]').forEach(el=>{
    el.addEventListener('click', (e)=>{
      const idx = parseInt(e.target.getAttribute('data-idx'));
      openAptosModal(idx);
    });
  });

  updateCatalogButtons();
}
function beforeSubmit(){
  document.getElementById('items_payload').value = JSON.stringify(items);
  return true;
}
// Prefill from server
console.log('Prefill items:', @json($prefill));
const prefill = @json($prefill);
(prefill||[]).forEach(p => {
  const aptIds = (Array.isArray(p.apartment_ids)
    ? [...p.apartment_ids] // CLONE para evitar compartir referencia entre ítems
    : []);

  items.push({
    expense_item_id: p.expense_item_id,
    name: p.name || ('Item ' + p.expense_item_id),
    amount: parseFloat(p.amount || 0),
    quantity: parseInt(p.quantity || 1),
    distribution: p.distribution || 'aliquota',
    apartment_ids: aptIds,
  });
});

wireCatalogButtons();
renderItems();
updateCatalogButtons();
// Simple filter
const filterInput = document.getElementById('item-filter');
filterInput?.addEventListener('input', (e)=>{
  const q = (e.target.value||'').toLowerCase();
  document.querySelectorAll('.item-row').forEach(row=>{
    const name = row.getAttribute('data-name')||'';
    row.style.display = name.includes(q.toLowerCase().replace(/\s+/g,'-')) ? '' : 'none';
  });
});

// Modal de selección de apartamentos por ítem
let currentIdx = null;
@php($aptosForJson = $apartments->map(function($ap) use ($towerMap) { return ['id'=>$ap->id,'code'=>$ap->code,'aliquot'=>$ap->aliquot_percent,'tower_id'=>$ap->tower_id,'tower_name'=>$towerMap[$ap->tower_id]??'']; })->values())
const allAptos = @json($aptosForJson);
function renderModalAptosList(){
  const list = document.getElementById('aptos-list');
  list.innerHTML = '';
  const filterText = (document.getElementById('apt-modal-filter')?.value || '').toLowerCase();
  const towerId = document.getElementById('apt-modal-tower')?.value || '';
  const selected = currentIdx!==null && Array.isArray(items[currentIdx].apartment_ids) ? items[currentIdx].apartment_ids : [];
  const filtered = allAptos.filter(ap => {
    const matchesText = !filterText || (ap.code.toLowerCase().includes(filterText) || (ap.tower_name||'').toLowerCase().includes(filterText));
    const matchesTower = !towerId || String(ap.tower_id) === String(towerId);
    return matchesText && matchesTower;
  });
  filtered.forEach(ap => {
    const div = document.createElement('div');
    div.className = 'form-check';
    const checked = selected.includes(ap.id) ? 'checked' : '';
    div.innerHTML = `<input class="form-check-input" type="checkbox" value="${ap.id}" id="modal_ap_${ap.id}" ${checked}>
                     <label class="form-check-label" for="modal_ap_${ap.id}">${ap.code} — ${ap.aliquot}% <span class="text-muted">(Torre: ${ap.tower_name||'-'})</span></label>`;
    list.appendChild(div);
  });
}
function openAptosModal(idx){
  currentIdx = idx;
  // Initialize filters
  const filterInput = document.getElementById('apt-modal-filter');
  const towerSelect = document.getElementById('apt-modal-tower');
  // Reset
  if(filterInput) filterInput.value = '';
  if(towerSelect) towerSelect.value = '';
  renderModalAptosList();
  const modal = new bootstrap.Modal(document.getElementById('aptosModal'));
  modal.show();
}
function saveAptosModal(){
  if(currentIdx===null) return;
  const list = document.querySelectorAll('#aptos-list input.form-check-input');
  const sel = [];
  list.forEach(i => { if(i.checked) sel.push(parseInt(i.value)); });
  items[currentIdx].apartment_ids = sel;
  currentIdx = null;
  renderItems();
}
function cancelAptosModal(){ currentIdx = null; }
</script>

<script>
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

function slugify(str){
  return String(str || '')
    .toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
    .replace(/[^a-z0-9]+/g,'-')
    .replace(/(^-|-$)/g,'');
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

    // Inject into catalog list
    const list = document.getElementById('expense-catalog');
    const wrapper = document.createElement('div');
    wrapper.className = 'd-flex align-items-center gap-2 mb-1 item-row';
    wrapper.setAttribute('data-name', slugify(payload.name));

    const label = (payload.type === 'aliquot') ? 'Alícuota' : 'Fijo';
    const span = document.createElement('span');
    span.className = 'flex-grow-1';
    span.innerHTML = `${escapeHtml(payload.name)} <span class="text-muted">(${label})</span>`;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-sm btn-outline-primary js-add-item';
    btn.dataset.expenseId = String(payload.id);
    btn.dataset.expenseName = String(payload.name);
    btn.textContent = 'Agregar';
    btn.addEventListener('click', () => {
      const id = parseInt(btn.dataset.expenseId);
      const n = btn.dataset.expenseName;
      addItem(id, n);
    });

    wrapper.appendChild(span);
    wrapper.appendChild(btn);

    // Add below the filter input group
    const filterGroup = list?.querySelector('.input-group');
    filterGroup?.insertAdjacentElement('afterend', wrapper);

    if(newExpenseModalInstance){ newExpenseModalInstance.hide(); }
    updateCatalogButtons();
  }catch(e){
    err.innerText = e?.message || 'Error.';
    err.classList.remove('d-none');
  }
}
</script>

<script>
// Global apartments filter (left column)
(function(){
  const container = document.getElementById('global-apartments-list');
  const input = document.getElementById('apt-global-filter');
  const tower = document.getElementById('apt-global-tower');
  const btnSelect = document.getElementById('apt-global-select-visible');
  const btnClear = document.getElementById('apt-global-clear-visible');
  function applyFilter(){
    const q = (input?.value || '').toLowerCase();
    const t = (tower?.value || '').trim();
    container?.querySelectorAll('.apartment-row').forEach(row => {
      const code = (row.getAttribute('data-code')||'').toLowerCase();
      const tId = (row.getAttribute('data-tower-id')||'').trim();
      const tName = (row.getAttribute('data-tower-name')||'').toLowerCase();
      const matchesText = !q || code.includes(q) || tName.includes(q);
      const matchesTower = !t || tId === t;
      row.style.display = (matchesText && matchesTower) ? '' : 'none';
    });
  }
  input?.addEventListener('input', applyFilter);
  tower?.addEventListener('change', applyFilter);
  // Apply initial filter state
  applyFilter();
  btnSelect?.addEventListener('click', ()=>{
    container?.querySelectorAll('.apartment-row').forEach(row=>{
      const cb = row.querySelector('input[type=checkbox]');
      if(row.style.display!== 'none' && cb) cb.checked = true;
    });
  });
  btnClear?.addEventListener('click', ()=>{
    container?.querySelectorAll('.apartment-row').forEach(row=>{
      const cb = row.querySelector('input[type=checkbox]');
      if(row.style.display!== 'none' && cb) cb.checked = false;
    });
  });
})();

// Bind modal filter inputs to re-render list
document.getElementById('apt-modal-filter')?.addEventListener('input', renderModalAptosList);
document.getElementById('apt-modal-tower')?.addEventListener('change', renderModalAptosList);
</script>

<!-- Modal Bootstrap -->
<div class="modal fade" id="aptosModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Seleccionar apartamentos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="cancelAptosModal()"></button>
      </div>
      <div class="modal-body">
            <div class="d-flex gap-2 align-items-center mb-2">
              <div class="input-group input-group-sm" style="max-width: 240px;">
                <span class="input-group-text">Buscar</span>
                <input type="text" id="apt-modal-filter" class="form-control" placeholder="Código/torre...">
              </div>
              <select id="apt-modal-tower" class="form-select form-select-sm" style="max-width:180px;">
                <option value="">Todas las torres</option>
                @foreach($towers as $t)
                  <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
              </select>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.querySelectorAll('#aptos-list .form-check input').forEach(i=>{ if(i.parentElement.style.display!=='none'){ i.checked=true; } })">Seleccionar visibles</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.querySelectorAll('#aptos-list .form-check input').forEach(i=>{ if(i.parentElement.style.display!=='none'){ i.checked=false; } })">Limpiar visibles</button>
            </div>
            <div id="aptos-list"></div>
          </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="cancelAptosModal()">Cancelar</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal" onclick="saveAptosModal()">Guardar</button>
      </div>
    </div>
  </div>
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
@endpush
