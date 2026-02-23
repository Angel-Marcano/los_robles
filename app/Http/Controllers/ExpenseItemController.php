<?php
namespace App\Http\Controllers;
use App\Models\ExpenseItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExpenseItemController extends Controller
{
    public function index(Request $r)
    {
        $this->authorize('viewAny', ExpenseItem::class);
        $items = ExpenseItem::query()->orderBy('name')->paginate(30)->appends($r->query());
        return view('expense-items.index', compact('items'));
    }

    public function create()
    {
        $this->authorize('create', ExpenseItem::class);
        return view('expense-items.create');
    }

    public function store(Request $r)
    {
        $this->authorize('create', ExpenseItem::class);
        $data = $r->validate([
            'name'   => 'required|string|max:120',
            'type'   => 'nullable|in:fixed,aliquot',
            'active' => 'required|boolean'
        ]);
        $data['type'] = $data['type'] ?? 'fixed';
        $data['active'] = (bool)$r->input('active');
        ExpenseItem::create($data);
        \Log::info('ExpenseItem creado', ['data'=>$data]);
        return redirect()->route('expense-items.index')->with('status','Item creado');
    }

    public function storeInline(Request $r): JsonResponse
    {
        $this->authorize('create', ExpenseItem::class);
        $data = $r->validate([
            'name'   => 'required|string|max:120',
            'type'   => 'nullable|in:fixed,aliquot',
            'active' => 'nullable|boolean',
        ]);
        $data['type'] = $data['type'] ?? 'fixed';
        $data['active'] = array_key_exists('active', $data) ? (bool)$data['active'] : true;

        $item = ExpenseItem::create($data);
        return response()->json([
            'id' => $item->id,
            'name' => $item->name,
            'type' => $item->type,
            'active' => (bool)$item->active,
        ], 201);
    }

    public function edit(ExpenseItem $expense_item)
    {
        $this->authorize('update', $expense_item);
        return view('expense-items.edit', ['item'=>$expense_item]);
    }

    public function update(Request $r, ExpenseItem $expense_item)
    {
        $this->authorize('update', $expense_item);
        $data = $r->validate([
            'name'   => 'required|string|max:120',
            'type'   => 'nullable|in:fixed,aliquot',
            'active' => 'required|boolean'
        ]);
        $data['type'] = $data['type'] ?? ($expense_item->type ?? 'fixed');
        $data['active'] = (bool)$r->input('active');
        $expense_item->update($data);
        \Log::info('ExpenseItem actualizado', ['id'=>$expense_item->id,'data'=>$data]);
        return redirect()->route('expense-items.index')->with('status','Item actualizado');
    }

    public function destroy(ExpenseItem $expense_item)
    {
        $this->authorize('delete', $expense_item);
        $expense_item->delete();
        return redirect()->route('expense-items.index')->with('status','Item eliminado');
    }
}
