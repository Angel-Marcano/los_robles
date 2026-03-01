@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center page-header">
    <div>
        <h1><i class="bi bi-file-earmark-text me-2"></i>{{ $isAdmin ? 'Facturas' : 'Mis Facturas' }}</h1>
    </div>
    @can('create', App\Models\Invoice::class)
    <a href="{{ route('invoices.create') }}" class="btn btn-primary btn-action">
        <i class="bi bi-plus-lg"></i> Nueva factura
    </a>
    @endcan
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" id="invoice-filters">
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Periodo</label>
                    <input type="month" name="period" value="{{ request('period') }}" class="form-control" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select">
                        <option value="">-- Todos --</option>
                        @foreach(($isAdmin ? ['draft'=>'Borrador','pending'=>'Pendiente','paid'=>'Pagada'] : ['pending'=>'Pendiente','paid'=>'Pagada']) as $val=>$label)
                            <option value="{{ $val }}" @selected(request('status')===$val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @if($isAdmin)
                <div class="col-md-2">
                    <label class="form-label">Mostrar</label>
                    <select name="type" class="form-select">
                        <option value="parent" @selected(!request()->has('type') || request('type')==='parent')>Padres</option>
                        <option value="child" @selected(request('type')==='child')>Hijas</option>
                        <option value="all" @selected(request('type')==='all')>Todas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Torre</label>
                    <select name="tower_id" class="form-select">
                        <option value="">-- Todas --</option>
                        @foreach($towers as $t)
                            <option value="{{ $t->id }}" {{ (string)request('tower_id') === (string)$t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Creada desde</label>
                    <input type="date" name="created_from" value="{{ request('created_from') }}" class="form-control" />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Creada hasta</label>
                    <input type="date" name="created_to" value="{{ request('created_to') }}" class="form-control" />
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">Por página</label>
                    <select name="per_page" class="form-select">
                        @foreach([10,20,50] as $size)
                            <option value="{{ $size }}" {{ (request()->has('per_page') ? (int)request('per_page') : $invoices->perPage()) == $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button class="btn btn-primary btn-action w-100"><i class="bi bi-funnel"></i> Filtrar</button>
                </div>
                <div class="col-md-2 align-self-end">
                    <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary btn-action w-100"><i class="bi bi-x-lg"></i> Limpiar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Periodo</th>
                    @if(!$isAdmin)<th>Apartamento</th>@endif
                    <th>Estado</th>
                    @if($isAdmin)
                    <th>Tipo</th>
                    <th>Torre</th>
                    @endif
                    <th class="text-end">Total USD</th>
                    <th class="text-end">Mora USD</th>
                    @if($isAdmin)<th>Creada</th>@endif
                    <th class="text-end">Acciones</th>
                    @if($isAdmin)<th>Sub-facturas</th>@endif
                </tr>
            </thead>
            <tbody id="invoice-accordion">
                @forelse($invoices as $inv)
                    @php
                        $isChild = !is_null($inv->parent_id);
                        $isParent = is_null($inv->parent_id) && ($inv->children_count ?? 0) > 0;
                        $rowId = 'inv-'.$inv->id;
                        $badgeClass = ($inv->status === 'paid') ? 'success' : (($inv->status === 'pending') ? 'warning' : 'secondary');

                        $towerLabel = null;
                        if($inv->tower){
                            $towerLabel = $inv->tower->name;
                        } elseif($inv->tower_id){
                            $towerLabel = 'Torre #'.$inv->tower_id;
                        } elseif($inv->apartment && $inv->apartment->tower){
                            $towerLabel = $inv->apartment->tower->name;
                        } elseif($isParent && $inv->children && $inv->children->count() > 0){
                            $names = $inv->children->pluck('apartment.tower.name')->filter()->unique()->values();
                            if($names->count() === 1){
                                $towerLabel = $names->first();
                            } elseif($names->count() > 1){
                                $show = $names->take(3)->implode(', ');
                                $towerLabel = $names->count() > 3 ? ($show.' (+'.($names->count()-3).')') : $show;
                            }
                        }
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $inv->number ?? ('#'.$inv->id) }}</td>
                        <td>{{ $inv->period }}</td>
                        @if(!$isAdmin)<td>{{ $inv->apartment->code ?? '' }}</td>@endif
                        <td><span class="badge bg-{{ $badgeClass }}">{{ $inv->statusLabel() }}</span></td>
                        @if($isAdmin)
                        <td>
                            @if($isParent)
                                <span class="badge bg-primary"><i class="bi bi-diagram-3 me-1"></i>PADRE</span>
                            @elseif($isChild)
                                <span class="badge bg-info text-dark"><i class="bi bi-arrow-return-right me-1"></i>HIJA</span>
                            @else
                                <span class="badge bg-secondary">SIMPLE</span>
                            @endif
                        </td>
                        <td>{{ $towerLabel ?: 'Condominio' }}</td>
                        @endif
                        <td class="text-end">{{ number_format($inv->total_usd,2) }}</td>
                        <td class="text-end">{{ number_format($inv->computeLateFeeUsd(),2) }}</td>
                        @if($isAdmin)<td>{{ $inv->created_at->format('Y-m-d') }}</td>@endif
                        <td class="text-end">
                            <a href="{{ route('invoices.show',$inv) }}" class="btn btn-sm btn-outline-primary btn-action"><i class="bi bi-eye"></i> Ver</a>
                            <a href="{{ route('invoices.pdf',$inv) }}" class="btn btn-sm btn-outline-secondary btn-action" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
                        </td>
                        @if($isAdmin)
                        <td>
                            @if($isParent)
                                <button class="btn btn-sm btn-outline-info btn-action" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $rowId }}">
                                    <i class="bi bi-chevron-down"></i> Sub ({{ $inv->children_count }})
                                </button>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @if($isAdmin && $isParent)
                        <tr class="collapse" id="{{ $rowId }}" data-bs-parent="#invoice-accordion">
                            <td colspan="10" class="p-0">
                                <div class="p-3" style="background: var(--bs-tertiary-bg);">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Número</th>
                                                <th>Apartamento</th>
                                                <th class="text-end">Total USD</th>
                                                <th>Estado</th>
                                                <th class="text-end"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($inv->children as $child)
                                                @php
                                                    $childBadge = ($child->status === 'paid') ? 'success' : (($child->status === 'pending') ? 'warning' : 'secondary');
                                                @endphp
                                                <tr>
                                                    <td>{{ $child->number ?? ('#'.$child->id) }}</td>
                                                    <td>{{ $child->apartment->code ?? ('#'.$child->apartment_id) }}</td>
                                                    <td class="text-end">{{ number_format($child->total_usd,2) }}</td>
                                                    <td><span class="badge bg-{{ $childBadge }}">{{ $child->statusLabel() }}</span></td>
                                                    <td class="text-end">
                                                        <a href="{{ route('invoices.show',$child) }}" class="btn btn-sm btn-outline-primary btn-action"><i class="bi bi-eye"></i></a>
                                                        <a href="{{ route('invoices.pdf',$child) }}" class="btn btn-sm btn-outline-secondary btn-action" target="_blank"><i class="bi bi-file-pdf"></i></a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-center text-muted py-2">Sin sub-facturas</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 10 : 6 }}">
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-text"></i>
                                <p>Sin registros</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($invoices->hasPages())
<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="small text-muted">Mostrando {{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} de {{ $invoices->total() }}</div>
    {{ $invoices->links() }}
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('invoice-filters');
    if (!form) return;

    var elements = form.querySelectorAll('input[name="period"], select[name="status"], select[name="type"], select[name="tower_id"], input[name="created_from"], input[name="created_to"], select[name="per_page"]');
    elements.forEach(function (el) {
        el.addEventListener('change', function () { form.submit(); });
        el.addEventListener('blur', function () {
            if (el.tagName === 'INPUT') form.submit();
        });
    });
});
</script>
@endpush