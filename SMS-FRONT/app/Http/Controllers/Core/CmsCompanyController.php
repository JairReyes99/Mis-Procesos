<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Core\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CmsCompanyController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Company::withCount('users')
                ->whereIn('status_id', [1, 2])
                ->select('companies.id', 'companies.name', 'companies.rfc', 'companies.status_id', 'companies.balance');

            return DataTables::of($data)->make(true);
        }

        return view('core.companies.index');
    }

    public function create()
    {
        return view('core.companies.create');
    }

    public function store(CompanyRequest $request)
    {
        $data     = $request->safe()->except('sms_price_per_segment');
        $data['settings'] = $this->buildSettings(null, $request->input('sms_price_per_segment'));

        Company::create($data);

        if ($request->ajax()) {
            return response()->json(['success' => 'Empresa creada correctamente.']);
        }

        return redirect()->route('management.companies.index')
            ->with('success', 'Empresa creada correctamente.');
    }

    public function edit(Request $request, string $id)
    {
        $company = Company::findOrFail($id);

        if ($request->ajax()) {
            return response()->json([
                ...$company->toArray(),
                'sms_price_per_segment' => $company->settings['sms_price_per_segment'] ?? null,
            ]);
        }

        return view('core.companies.edit', compact('company'));
    }

    public function update(CompanyRequest $request, string $id)
    {
        $company  = Company::findOrFail($id);
        $data     = $request->safe()->except('sms_price_per_segment');
        $data['settings'] = $this->buildSettings($company->settings, $request->input('sms_price_per_segment'));

        $company->update($data);

        if ($request->ajax()) {
            return response()->json(['success' => 'Empresa actualizada correctamente.']);
        }

        return redirect()->route('management.companies.index')
            ->with('success', 'Empresa actualizada correctamente.');
    }

    private function buildSettings(?array $existing, mixed $smsPrice): array
    {
        $settings = $existing ?? [];

        if ($smsPrice !== null && $smsPrice !== '') {
            $settings['sms_price_per_segment'] = (float) $smsPrice;
        } else {
            unset($settings['sms_price_per_segment']);
        }

        return $settings;
    }

    public function toggleStatus(string $id)
    {
        $company = Company::findOrFail($id);
        $company->status_id = $company->status_id == 1 ? 2 : 1;
        $company->save();

        $msg = $company->status_id == 1
            ? 'Empresa activada correctamente.'
            : 'Empresa desactivada correctamente.';

        return response()->json(['status' => 'success', 'message' => $msg]);
    }

    public function destroy(string $id)
    {
        $company = Company::findOrFail($id);

        if ($company->users()->count() > 0) {
            return response()->json(['status' => 'error', 'message' => 'No se puede eliminar una empresa que tiene usuarios asignados.'], 422);
        }

        $company->status_id = 3;
        $company->save();

        return response()->json(['status' => 'success', 'message' => 'Empresa eliminada correctamente.']);
    }
}
