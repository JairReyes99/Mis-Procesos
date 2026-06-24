<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        $user      = auth()->user();
        $companies = null;

        if (! $user->company_id) {
            $companies = Company::where('status_id', 1)
                ->orderBy('name')
                ->get(['id', 'name', 'balance']);
        }

        return view('admin.dashboard.index', compact('companies'));
    }

    public function data(Request $request): JsonResponse
    {
        $user    = auth()->user();
        $period  = $request->get('period', 'today');
        $company = null;

        if ($user->company_id) {
            $companyId = (int) $user->company_id;
            $company   = $user->company;
        } else {
            $companyId = $request->get('company_id') ? (int) $request->get('company_id') : null;
            $company   = $companyId ? Company::find($companyId) : null;
        }

        [$from, $to]     = $this->dateRange($period);
        [$prevFrom, $prevTo] = $this->previousDateRange($period);

        $sent   = $this->sentCount($companyId, $from, $to);
        $failed = $this->failedCount($companyId, $from, $to);
        $total  = $sent + $failed;
        $rate   = $total > 0 ? round($sent / $total * 100, 1) : 0.0;

        $prevSent   = $this->sentCount($companyId, $prevFrom, $prevTo);
        $prevFailed = $this->failedCount($companyId, $prevFrom, $prevTo);
        $prevTotal  = $prevSent + $prevFailed;
        $prevRate   = $prevTotal > 0 ? round($prevSent / $prevTotal * 100, 1) : 0.0;

        $activeCampaigns = Campaign::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereIn('campaign_status', [2, 3])
            ->count();

        $spend     = $this->periodSpend($companyId, $from, $to);
        $prevSpend = $this->periodSpend($companyId, $prevFrom, $prevTo);

        return response()->json([
            'kpis' => [
                'sent'             => number_format($sent),
                'sent_delta'       => $this->pctDelta($sent, $prevSent),
                'failed'           => number_format($failed),
                'failed_delta'     => $this->pctDelta($failed, $prevFailed),
                'rate'             => $rate,
                'rate_delta'       => $prevTotal > 0 ? round($rate - $prevRate, 1) : null,
                'active_campaigns' => $activeCampaigns,
                'spend'            => '$' . number_format($spend, 2),
                'spend_delta'      => $this->pctDelta($spend, $prevSpend),
                'balance'          => $company ? '$' . number_format((float) $company->balance, 2) : null,
            ],
            'timeline'         => $this->timelineData($companyId, $period, $from, $to),
            'donut'            => $this->statusDonut($companyId, $from, $to),
            'top_campaigns'    => $this->topCampaigns($companyId, $from, $to),
            'recent_campaigns' => $this->recentCampaigns($companyId),
            'spend_recharges'  => $this->spendVsRecharges($companyId, $period, $from, $to),
            'top_companies'    => $companyId === null ? $this->topCompanies($from, $to) : null,
        ]);
    }

    // -------------------------------------------------------------------------

    private function dateRange(string $period): array
    {
        $now = now(config('app.timezone'));

        return match ($period) {
            '7d'    => [$now->copy()->subDays(6)->startOfDay(),  $now->copy()->endOfDay()],
            '30d'   => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->startOfMonth(),             $now->copy()->endOfDay()],
            default => [$now->copy()->startOfDay(),               $now->copy()->endOfDay()],
        };
    }

    private function baseQuery(?int $companyId)
    {
        return DB::table('campaign_recipients as cr')
            ->join('campaigns as c', 'c.id', '=', 'cr.campaign_id')
            ->whereNull('c.deleted_at')
            ->when($companyId, fn($q) => $q->where('c.company_id', $companyId));
    }

    private function sentCount(?int $companyId, $from, $to): int
    {
        return (int) $this->baseQuery($companyId)
            ->where('cr.send_status', 2)
            ->whereBetween('cr.sent_at', [$from, $to])
            ->count();
    }

    private function failedCount(?int $companyId, $from, $to): int
    {
        return (int) $this->baseQuery($companyId)
            ->where('cr.send_status', 3)
            ->whereBetween('cr.updated_at', [$from, $to])
            ->count();
    }

    private function periodSpend(?int $companyId, $from, $to): float
    {
        return (float) (DB::table('company_credit_transactions')
            ->where('type', 2)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount') ?? 0);
    }

    private function timelineData(?int $companyId, string $period, $from, $to): array
    {
        $byHour = ($period === 'today');

        $sentRows = $this->baseQuery($companyId)
            ->where('cr.send_status', 2)
            ->whereBetween('cr.sent_at', [$from, $to])
            ->selectRaw(
                $byHour
                    ? 'DATEPART(HOUR, cr.sent_at) as lbl, COUNT(*) as cnt'
                    : 'CAST(cr.sent_at AS DATE) as lbl, COUNT(*) as cnt'
            )
            ->groupByRaw($byHour ? 'DATEPART(HOUR, cr.sent_at)' : 'CAST(cr.sent_at AS DATE)')
            ->orderByRaw($byHour ? 'DATEPART(HOUR, cr.sent_at)' : 'CAST(cr.sent_at AS DATE)')
            ->get();

        $failedRows = $this->baseQuery($companyId)
            ->where('cr.send_status', 3)
            ->whereBetween('cr.updated_at', [$from, $to])
            ->selectRaw(
                $byHour
                    ? 'DATEPART(HOUR, cr.updated_at) as lbl, COUNT(*) as cnt'
                    : 'CAST(cr.updated_at AS DATE) as lbl, COUNT(*) as cnt'
            )
            ->groupByRaw($byHour ? 'DATEPART(HOUR, cr.updated_at)' : 'CAST(cr.updated_at AS DATE)')
            ->orderByRaw($byHour ? 'DATEPART(HOUR, cr.updated_at)' : 'CAST(cr.updated_at AS DATE)')
            ->get();

        if ($byHour) {
            $labels     = array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23));
            $sentArr    = array_fill(0, 24, 0);
            $failedArr  = array_fill(0, 24, 0);

            foreach ($sentRows as $row)   $sentArr[(int)  $row->lbl] = (int) $row->cnt;
            foreach ($failedRows as $row) $failedArr[(int) $row->lbl] = (int) $row->cnt;

            return ['labels' => $labels, 'sent' => $sentArr, 'failed' => $failedArr];
        }

        $days   = (int) $from->diffInDays($to) + 1;
        $labels = [];
        for ($i = 0; $i < $days; $i++) {
            $labels[] = $from->copy()->addDays($i)->format('Y-m-d');
        }

        $sentMap   = $sentRows->keyBy(fn($r)   => substr((string) $r->lbl, 0, 10));
        $failedMap = $failedRows->keyBy(fn($r) => substr((string) $r->lbl, 0, 10));

        $sentArr   = array_map(fn($d) => (int) ($sentMap[$d]->cnt   ?? 0), $labels);
        $failedArr = array_map(fn($d) => (int) ($failedMap[$d]->cnt ?? 0), $labels);
        $labels    = array_map(fn($d) => date('d/m', strtotime($d)), $labels);

        return ['labels' => $labels, 'sent' => $sentArr, 'failed' => $failedArr];
    }

    private function statusDonut(?int $companyId, $from, $to): array
    {
        $rows = $this->baseQuery($companyId)
            ->whereBetween('cr.created_at', [$from, $to])
            ->selectRaw('cr.send_status, COUNT(*) as cnt')
            ->groupBy('cr.send_status')
            ->get()
            ->keyBy('send_status');

        return [
            'series' => [
                (int) ($rows[2]->cnt ?? 0),
                (int) ($rows[3]->cnt ?? 0),
                (int) ($rows[1]->cnt ?? 0),
                (int) ($rows[4]->cnt ?? 0),
            ],
            'labels' => ['Enviado', 'Fallido', 'Pendiente', 'Bloqueado'],
        ];
    }

    private function topCampaigns(?int $companyId, $from, $to): array
    {
        $rows = DB::table('campaigns as c')
            ->whereNull('c.deleted_at')
            ->when($companyId, fn($q) => $q->where('c.company_id', $companyId))
            ->whereBetween('c.created_at', [$from, $to])
            ->orderByDesc('c.sent_count')
            ->limit(7)
            ->select('c.name', 'c.sent_count', 'c.failed_count')
            ->get();

        return [
            'names'  => $rows->pluck('name')->toArray(),
            'sent'   => $rows->pluck('sent_count')->map(fn($v) => (int) $v)->toArray(),
            'failed' => $rows->pluck('failed_count')->map(fn($v) => (int) $v)->toArray(),
        ];
    }

    private function previousDateRange(string $period): array
    {
        $now = now(config('app.timezone'));

        return match ($period) {
            '7d'    => [$now->copy()->subDays(13)->startOfDay(), $now->copy()->subDays(7)->endOfDay()],
            '30d'   => [$now->copy()->subDays(59)->startOfDay(), $now->copy()->subDays(30)->endOfDay()],
            'month' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            default => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
        };
    }

    private function pctDelta($current, $previous): ?float
    {
        return $previous > 0 ? round(($current - $previous) / $previous * 100, 1) : null;
    }

    private function spendVsRecharges(?int $companyId, string $period, $from, $to): array
    {
        $byHour = ($period === 'today');

        $rows = DB::table('company_credit_transactions')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw(
                ($byHour
                    ? 'DATEPART(HOUR, created_at) as lbl'
                    : 'CAST(created_at AS DATE) as lbl')
                . ', type, SUM(CAST(amount AS FLOAT)) as total'
            )
            ->groupByRaw(
                ($byHour ? 'DATEPART(HOUR, created_at)' : 'CAST(created_at AS DATE)')
                . ', type'
            )
            ->orderByRaw($byHour ? 'DATEPART(HOUR, created_at)' : 'CAST(created_at AS DATE)')
            ->get();

        if ($byHour) {
            $labels    = array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23));
            $recharges = array_fill(0, 24, 0.0);
            $spend     = array_fill(0, 24, 0.0);
            foreach ($rows as $row) {
                $h = (int) $row->lbl;
                if ((int) $row->type === 1) $recharges[$h] = round((float) $row->total, 2);
                if ((int) $row->type === 2) $spend[$h]     = round((float) $row->total, 2);
            }
            return compact('labels', 'recharges', 'spend');
        }

        $days    = (int) $from->diffInDays($to) + 1;
        $dayList = [];
        for ($i = 0; $i < $days; $i++) {
            $dayList[] = $from->copy()->addDays($i)->format('Y-m-d');
        }

        $rechargesMap = [];
        $spendMap     = [];
        foreach ($rows as $row) {
            $d = substr((string) $row->lbl, 0, 10);
            if ((int) $row->type === 1) $rechargesMap[$d] = round((float) $row->total, 2);
            if ((int) $row->type === 2) $spendMap[$d]     = round((float) $row->total, 2);
        }

        $recharges = array_map(fn($d) => $rechargesMap[$d] ?? 0.0, $dayList);
        $spend     = array_map(fn($d) => $spendMap[$d] ?? 0.0, $dayList);
        $labels    = array_map(fn($d) => date('d/m', strtotime($d)), $dayList);

        return compact('labels', 'recharges', 'spend');
    }

    private function topCompanies($from, $to): array
    {
        $rows = DB::table('campaigns as c')
            ->join('companies as co', 'co.id', '=', 'c.company_id')
            ->whereNull('c.deleted_at')
            ->whereBetween('c.created_at', [$from, $to])
            ->selectRaw('co.name, SUM(c.sent_count) as total_sent, SUM(c.failed_count) as total_failed')
            ->groupBy('co.id', 'co.name')
            ->orderByDesc('total_sent')
            ->limit(8)
            ->get();

        return [
            'names'  => $rows->pluck('name')->toArray(),
            'sent'   => $rows->pluck('total_sent')->map(fn($v) => (int) $v)->toArray(),
            'failed' => $rows->pluck('total_failed')->map(fn($v) => (int) $v)->toArray(),
        ];
    }

    private function recentCampaigns(?int $companyId): array
    {
        return Campaign::with('statusCatalog')
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'status_label' => $c->status_label,
                'status_color' => $c->status_color,
                'sent'         => number_format((int) $c->sent_count),
                'failed'       => number_format((int) $c->failed_count),
                'total'        => number_format((int) $c->total_recipients),
                'rate'         => $c->total_recipients > 0
                    ? round($c->sent_count / $c->total_recipients * 100, 1)
                    : 0.0,
                'cost'         => '$' . number_format((float) $c->charged_cost, 2),
                'date'         => $c->created_at->format('d/m/Y'),
                'url'          => route('sms.campaigns.show', $c->id),
            ])
            ->toArray();
    }
}
