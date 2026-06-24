<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CampaignRequest;
use App\Models\Campaign;
use App\Models\CampaignSendType;
use App\Models\CampaignStatus;
use App\Models\Company;
use App\Repositories\CampaignRepository;
use App\Services\CampaignService;
use App\Services\CompanyCreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class CampaignController extends Controller
{
    public function __construct(
        private CampaignRepository $repo,
        private CampaignService $service,
        private CompanyCreditService $credits
    ) {}

    // ─── Index ───────────────────────────────────────────────────────────────────

    /**
     * GET /sms/campaigns
     * Listado con DataTables. Si es AJAX retorna JSON para la tabla.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->repo->datatable();

            // Filtros
            $query->when($request->filled('filter_status'), fn ($q) =>
                $q->where('campaigns.campaign_status', $request->integer('filter_status'))
            );
            $query->when($request->filled('filter_from'), fn ($q) =>
                $q->whereDate('campaigns.created_at', '>=', $request->input('filter_from'))
            );
            $query->when($request->filled('filter_to'), fn ($q) =>
                $q->whereDate('campaigns.created_at', '<=', $request->input('filter_to'))
            );

            return DataTables::of($query)
                ->rawColumns(['actions'])
                ->make(true);
        }

        $statuses = CampaignStatus::orderBy('order')->get();

        return view('admin.campaigns.index', compact('statuses'));
    }

    // ─── Create ──────────────────────────────────────────────────────────────────

    /**
     * GET /sms/campaigns/create
     */
    public function create()
    {
        $sendTypes = CampaignSendType::where('status_id', 1)
            ->orderBy('order')
            ->get();

        $companyEmail = auth()->user()->email ?? auth()->user()->company?->email ?? null;

        return view('admin.campaigns.create', compact('sendTypes', 'companyEmail'));
    }

    // ─── Store ───────────────────────────────────────────────────────────────────

    /**
     * POST /sms/campaigns
     * Recibe el archivo ya cargado (temp_path) más los datos del formulario.
     */
    public function store(CampaignRequest $request)
    {
        $validated = $request->validated();

        // temp_path viene fuera de CampaignRequest (se valida manualmente)
        $request->validate([
            'temp_path'               => ['required', 'string'],
            'phone_col'               => ['required', 'string', 'regex:/^[A-Z]{1,3}$/i'],
            'message_col'             => ['nullable', 'string', 'regex:/^[A-Z]{1,3}$|^__fixed__$|^__concat__$/i'],
            'fixed_message'           => ['required_if:message_col,__fixed__', 'nullable', 'string', 'max:1600'],
            'message_concat_cols'     => ['required_if:message_col,__concat__', 'nullable', 'array', 'min:1'],
            'message_concat_cols.*'   => ['nullable', 'string', 'regex:/^[A-Z]{1,3}$/i'],
            'message_concat_seps'     => ['nullable', 'array'],
            'message_concat_seps.*'   => ['nullable', 'string', 'max:20'],
        ], [
            'temp_path.required'              => 'El archivo de destinatarios es obligatorio. Por favor sube un archivo Excel.',
            'phone_col.required'              => 'Debes seleccionar la columna que contiene los números de teléfono.',
            'phone_col.regex'                 => 'La columna de teléfono no es válida.',
            'message_col.regex'               => 'La columna de mensaje seleccionada no es válida.',
            'fixed_message.required_if'       => 'Debes escribir el mensaje fijo que se enviará a todos los destinatarios.',
            'fixed_message.max'               => 'El mensaje fijo no puede superar los 1,600 caracteres.',
            'message_concat_cols.required_if' => 'Debes configurar al menos dos columnas para el modo de mensaje concatenado.',
            'message_concat_cols.min'         => 'El modo concatenado requiere al menos dos columnas.',
            'message_concat_cols.*.regex'     => 'Una de las columnas del mensaje concatenado no es válida. Vuelve a seleccionar las columnas.',
            'message_concat_seps.*.max'       => 'Uno de los separadores del mensaje supera los 20 caracteres permitidos.',
        ]);

        $tempPath     = $request->input('temp_path');
        $absolutePath = Storage::disk('local')->path($tempPath);

        // Prevenir path traversal: el archivo debe estar dentro de campaigns/temp/
        $baseTempPath = realpath(Storage::disk('local')->path('campaigns/temp'));
        $realFilePath = file_exists($absolutePath) ? realpath($absolutePath) : false;
        if (!$baseTempPath || $realFilePath === false || !str_starts_with($realFilePath, $baseTempPath . DIRECTORY_SEPARATOR)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'El archivo de destinatarios no se encontró. Por favor, sube el archivo nuevamente.');
        }

        // Resolver modo de mensaje
        $rawMessageCol = $request->input('message_col');
        $messageCols   = null;
        $messageSeps   = null;

        if ($rawMessageCol === '__concat__') {
            $messageCols = $request->input('message_concat_cols', []);
            $messageSeps = $request->input('message_concat_seps', []);
            $messageCol  = null;
        } elseif ($rawMessageCol === '__fixed__' || !$rawMessageCol) {
            $messageCol = null;
        } else {
            $messageCol = $rawMessageCol;
        }

        try {
            $recipients = $this->service->parseExcelRows(
                $absolutePath,
                $request->input('phone_col'),
                $messageCol,
                $request->input('fixed_message'),
                null,
                $messageCols,
                $messageSeps
            );

            if (empty($recipients)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'No se encontraron destinatarios válidos en el archivo.');
            }

            // Validar saldo antes de crear la campaña
            $companyId     = $validated['company_id'] ?? auth()->user()->company_id;
            $company       = Company::find($companyId);
            $balanceWarning = null;

            if ($company) {
                $totalSegments = array_sum(array_column($recipients, 'segments'));
                $current       = (float) $company->balance;

                if ($current <= 0) {
                    // Sin saldo: bloquear
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Saldo insuficiente. Tu empresa no tiene crédito disponible. Recarga para poder crear esta campaña.');
                }

                if (!$this->credits->hasSufficientBalance($company, $totalSegments)) {
                    // Saldo parcial: permitir pero advertir
                    $balanceWarning = 'Tu saldo actual no alcanza para todos los destinatarios. La campaña se creará y los mensajes se enviarán hasta agotar el crédito disponible.';
                }
            }

            $campaign = $this->repo->createWithRecipients($validated, $recipients);

            // Limpiar archivo temporal
            $this->service->deleteTempFile($tempPath);

            $redirect = redirect()->route('sms.campaigns.show', $campaign->uuid)
                ->with('success', 'Campaña "' . $campaign->name . '" creada correctamente con ' . count($recipients) . ' destinatarios.');

            return $balanceWarning
                ? $redirect->with('warning', $balanceWarning)
                : $redirect;
        } catch (\Throwable $e) {
            // Si el archivo sigue ahí, intentar borrarlo
            $this->service->deleteTempFile($tempPath);

            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Ocurrió un error al crear la campaña. Por favor intenta de nuevo.');
        }
    }

    // ─── Show ────────────────────────────────────────────────────────────────────

    /**
     * GET /sms/campaigns/{uuid}
     */
    public function show(string $uuid)
    {
        $campaign = Campaign::with(['creator', 'sendType'])->where('uuid', $uuid)->firstOrFail();

        $recipients = $campaign->recipients()
            ->with('sendStatusCatalog')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.campaigns.show', compact('campaign', 'recipients'));
    }

    // ─── Destroy (Cancel) ────────────────────────────────────────────────────────

    /**
     * DELETE /sms/campaigns/{uuid}
     * Cancela la campaña si está en Programada.
     */
    public function destroy(string $uuid)
    {
        $campaign = Campaign::where('uuid', $uuid)->firstOrFail();

        $cancelled = $this->repo->cancel($campaign);

        if (!$cancelled) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Solo se pueden cancelar campañas en estado Programada.',
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Campaña cancelada correctamente.',
        ]);
    }

    // ─── Pause ───────────────────────────────────────────────────────────────────

    /**
     * PATCH /sms/campaigns/{uuid}/pause
     * Pausa una campaña en estado Programada (2) o Procesando (3).
     */
    public function pause(string $uuid)
    {
        $campaign = Campaign::with('company')->where('uuid', $uuid)->firstOrFail();

        // Cobrar el delta de mensajes ya enviados antes de pausar
        $sentCost = round(
            (float) $campaign->recipients()->where('send_status', 2)->sum('cost'),
            4
        );
        $delta = round($sentCost - (float) $campaign->charged_cost, 4);

        if ($delta > 0) {
            $segments = (int) $campaign->recipients()->where('send_status', 2)->sum('segments') ?: 0;
            try {
                $this->credits->charge(
                    $campaign->company,
                    $delta,
                    "Campaña #{$campaign->id} — {$campaign->name} (pausa, {$segments} seg. enviados)",
                    null,
                    (int) $campaign->created_by
                );
                $campaign->charged_cost = $sentCost;
            } catch (\Exception $e) {
                \Log::error("Campaign #{$campaign->id} pause charge failed: " . $e->getMessage());
            }
        }

        if (!$this->repo->pause($campaign)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Solo se pueden pausar campañas en estado Programada o Procesando.',
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Campaña pausada correctamente.',
        ]);
    }

    // ─── Resume ──────────────────────────────────────────────────────────────────

    /**
     * PATCH /sms/campaigns/{uuid}/resume
     * Reanuda una campaña pausada (5).
     */
    public function resume(string $uuid)
    {
        $campaign = Campaign::with(['company', 'sendType'])->where('uuid', $uuid)->firstOrFail();

        // Validar saldo suficiente para los mensajes pendientes que faltan enviar
        $pendingSegments = (int) $campaign->recipients()->where('send_status', 1)->sum('segments');

        if ($pendingSegments > 0 && (float) $campaign->company->balance <= 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Sin saldo disponible. Recarga el crédito de tu empresa para reanudar esta campaña.',
            ], 422);
        }

        if (!$this->repo->resume($campaign)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Solo se pueden reanudar campañas en estado Pausada.',
            ], 422);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Campaña reanudada correctamente.',
        ]);
    }

    // ─── AJAX: Upload file ───────────────────────────────────────────────────────

    /**
     * POST /sms/campaigns/ajax/upload-file
     * Sube el archivo Excel y retorna las cabeceras detectadas.
     */
    public function uploadFile(Request $request)
    {
        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $file = $request->file('excel_file');

        // Guardar temporalmente
        $tempPath     = $this->service->storeTempFile($file);
        $absolutePath = Storage::disk('local')->path($tempPath);

        try {
            $totalRecords = $this->service->countRows($absolutePath);
        } catch (\Throwable $e) {
            $this->service->deleteTempFile($tempPath);
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'El archivo no pudo procesarse. Verifica que sea un Excel válido.',
            ], 422);
        }

        if ($totalRecords === 0) {
            $this->service->deleteTempFile($tempPath);

            return response()->json([
                'status'  => 'error',
                'message' => 'El archivo no contiene filas de datos.',
            ], 422);
        }

        return response()->json([
            'status'        => 'success',
            'temp_path'     => $tempPath,
            'total_records' => $totalRecords,
        ]);
    }

    // ─── AJAX: Preview ───────────────────────────────────────────────────────────

    /**
     * POST /sms/campaigns/ajax/preview
     * Previsualiza los primeros 50 destinatarios del archivo cargado.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'temp_path'             => ['required', 'string'],
            'phone_col'             => ['required', 'string'],
            'message_col'           => ['nullable', 'string'],
            'fixed_message'         => ['nullable', 'string'],
            'message_concat_cols'   => ['nullable', 'array'],
            'message_concat_cols.*' => ['string'],
            'message_concat_seps'   => ['nullable', 'array'],
            'message_concat_seps.*' => ['nullable', 'string', 'max:20'],
        ]);

        $tempPath     = $request->input('temp_path');
        $absolutePath = Storage::disk('local')->path($tempPath);

        // Prevenir path traversal
        $baseTempPath = realpath(Storage::disk('local')->path('campaigns/temp'));
        $realFilePath = file_exists($absolutePath) ? realpath($absolutePath) : false;
        if (!$baseTempPath || $realFilePath === false || !str_starts_with($realFilePath, $baseTempPath . DIRECTORY_SEPARATOR)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El archivo temporal no existe. Por favor, sube el archivo nuevamente.',
            ], 404);
        }

        // Resolver modo de mensaje
        $rawMessageCol = $request->input('message_col');
        $messageCols   = null;
        $messageSeps   = null;

        if ($rawMessageCol === '__concat__') {
            $messageCols = $request->input('message_concat_cols', []);
            $messageSeps = $request->input('message_concat_seps', []);
            $messageCol  = null;
        } elseif ($rawMessageCol === '__fixed__' || !$rawMessageCol) {
            $messageCol = null;
        } else {
            $messageCol = $rawMessageCol;
        }

        try {
            $records = $this->service->parseExcelRows(
                $absolutePath,
                $request->input('phone_col'),
                $messageCol,
                $request->input('fixed_message'),
                50,
                $messageCols,
                $messageSeps
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al procesar el archivo.',
            ], 422);
        }

        return response()->json([
            'status'        => 'success',
            'total_records' => count($records),
            'records'       => $records,
        ]);
    }

    // ─── AJAX: Count phone validity ──────────────────────────────────────────────

    /**
     * POST /sms/campaigns/ajax/count-phones
     * Cuenta teléfonos válidos e inválidos en TODO el archivo para la columna dada.
     */
    public function countPhones(Request $request)
    {
        $request->validate([
            'temp_path' => ['required', 'string'],
            'phone_col' => ['required', 'string', 'regex:/^[A-Z]{1,3}$/i'],
        ]);

        $tempPath     = $request->input('temp_path');
        $absolutePath = Storage::disk('local')->path($tempPath);

        $baseTempPath = realpath(Storage::disk('local')->path('campaigns/temp'));
        $realFilePath = file_exists($absolutePath) ? realpath($absolutePath) : false;
        if (!$baseTempPath || $realFilePath === false || !str_starts_with($realFilePath, $baseTempPath . DIRECTORY_SEPARATOR)) {
            return response()->json(['status' => 'error', 'message' => 'Archivo no encontrado.'], 404);
        }

        try {
            $counts = $this->service->countPhoneValidity($absolutePath, $request->input('phone_col'));
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Error al contar registros.'], 422);
        }

        return response()->json(array_merge(['status' => 'success'], $counts));
    }

    // ─── AJAX: Calculate segments ────────────────────────────────────────────────

    /**
     * POST /sms/campaigns/ajax/segments
     * Calcula segmentos y encoding de un mensaje.
     */
    public function calculateSegments(Request $request)
    {
        $request->validate([
            'message' => ['required', 'string'],
        ]);

        $result = $this->service->calculateSegments($request->input('message'));

        return response()->json(array_merge(['status' => 'success'], $result));
    }
}
