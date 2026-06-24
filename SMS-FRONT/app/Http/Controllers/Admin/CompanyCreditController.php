<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyCreditTransaction;
use App\Services\CompanyCreditService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CompanyCreditController extends Controller
{
    public function __construct(private CompanyCreditService $credits) {}

    public function index(Request $request, string $company)
    {
        $company = Company::findOrFail($company);

        if ($request->ajax()) {
            $data = CompanyCreditTransaction::with('creator')
                ->where('company_id', $company->id)
                ->select('company_credit_transactions.*');

            return DataTables::of($data)
                ->addColumn('type_label', fn ($r) => $r->type_label)
                ->addColumn('type_color', fn ($r) => $r->type_color)
                ->addColumn('creator_name', fn ($r) => $r->creator?->name ?? '—')
                ->make(true);
        }

        return view('admin.companies.credits.index', compact('company'));
    }

    public function store(Request $request, string $company)
    {
        $company = Company::findOrFail($company);

        $validated = $request->validate([
            'type'    => 'required|in:1,2',
            'amount'  => 'required|numeric|min:0.01',
            'concept' => 'required|string|max:255',
            'notes'   => 'nullable|string|max:1000',
        ], [], [
            'type'    => 'tipo',
            'amount'  => 'monto',
            'concept' => 'concepto',
            'notes'   => 'notas',
        ]);

        try {
            $type = (int) $validated['type'];

            if ($type === 1) {
                $this->credits->credit(
                    $company,
                    (float) $validated['amount'],
                    $validated['concept'],
                    $validated['notes'] ?? null,
                );
            } else {
                $this->credits->charge(
                    $company,
                    (float) $validated['amount'],
                    $validated['concept'],
                    $validated['notes'] ?? null,
                );
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status'            => 'success',
            'message'           => 'Movimiento registrado correctamente.',
            'balance_formatted' => '$' . number_format($company->fresh()->balance, 2),
        ]);
    }
}
