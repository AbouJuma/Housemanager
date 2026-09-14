<?php

namespace App\Modules\AuditLogs\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AuditLogs\Models\ActivityLogEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityLogEntry::class);

        $companyId = $request->user()->isSaasOwner() ? null : $request->user()->company_id;
        $logName = $request->string('log_name')->toString() ?: null;

        $logs = ActivityLogEntry::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($logName, fn ($query) => $query->where('log_name', $logName))
            ->with(['causer', 'subject', 'company'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('audit-logs::index', [
            'logs' => $logs,
            'logNames' => ActivityLogEntry::query()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->distinct()
                ->orderBy('log_name')
                ->pluck('log_name'),
        ]);
    }
}
