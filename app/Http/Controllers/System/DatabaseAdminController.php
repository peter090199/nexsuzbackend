<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\DataResetService;
use App\Services\DatabaseAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** DEF-MASTERADMIN only (route middleware). Every state-changing action is written to the log with who did it. */
class DatabaseAdminController extends Controller
{
    public function __construct(private DatabaseAdminService $service, private DataResetService $reset) { }

    private function audit(string $action, array $context = []): void
    {
        Log::info("database-admin: {$action}", ['by' => Auth::user()->code ?? null] + $context);
    }

    public function server(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->serverInfo()]);
    }

    public function storage(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->storageInfo()]);
    }

    public function tables(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->tables()]);
    }

    /** POST /database-admin/tables/{action}  action = optimize | analyze,  body: tables[] */
    public function maintain(Request $request, string $action): JsonResponse
    {
        abort_unless(in_array($action, ['optimize', 'analyze'], true), 404);
        $data = $request->validate([
            'tables'   => ['required', 'array', 'min:1', 'max:' . DatabaseAdminService::MAX_TABLES_PER_ACTION],
            'tables.*' => ['required', 'string', 'max:128'],
        ]);

        $unknown = array_values(array_diff($data['tables'], $this->service->tableNames()));
        if ($unknown) {
            return response()->json(['success' => false, 'message' => 'Unknown table: ' . $unknown[0]], 422);
        }

        $results = $this->service->maintain($action, $data['tables']);
        $this->audit($action, ['tables' => $data['tables']]);

        return response()->json(['success' => true, 'message' => ucfirst($action) . ' finished for ' . count($results) . ' table(s).', 'data' => $results]);
    }

    public function backups(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->service->listBackups()]);
    }

    public function createBackup(): JsonResponse
    {
        try {
            $backup = $this->service->createBackup();
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'The backup could not be created.'], 500);
        }
        $this->audit('backup created', ['file' => $backup['name']]);

        return response()->json(['success' => true, 'message' => 'Backup created.', 'data' => $backup], 201);
    }

    public function downloadBackup(string $name): BinaryFileResponse|JsonResponse
    {
        $path = $this->service->backupPath($name);
        if (!$path) {
            return response()->json(['success' => false, 'message' => 'Backup not found.'], 404);
        }
        $this->audit('backup downloaded', ['file' => $name]);

        return response()->download($path, $name, ['Content-Type' => 'application/gzip']);
    }

    // ------------------------------------------------------------ reset all data

    /** What a reset would remove and keep, plus whether it is allowed here. Read-only. */
    public function resetPreview(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->reset->preview() + [
                'allowed' => (bool) config('databaseadmin.allow_reset'),
                'phrase' => config('databaseadmin.reset_phrase'),
                'protected_roles' => config('databaseadmin.protected_roles'),
            ],
        ]);
    }

    /**
     * POST /database-admin/reset
     * Removes every account that is not DEF-ADMIN / DEF-MASTERADMIN and everything they created.
     * Order matters: every check first, then a mandatory backup, and only then the delete.
     */
    public function resetAll(Request $request): JsonResponse
    {
        if (!config('databaseadmin.allow_reset')) {
            return response()->json(['success' => false,
                'message' => 'Resetting data is switched off on this server. Set DB_ADMIN_ALLOW_RESET=true to enable it.'], 403);
        }

        $data = $request->validate([
            'password'     => ['required', 'string'],
            'confirm'      => ['required', 'string'],
            'delete_files' => ['boolean'],
        ]);

        if (!hash_equals((string) config('databaseadmin.reset_phrase'), trim($data['confirm']))) {
            return response()->json(['success' => false, 'message' => 'Type ' . config('databaseadmin.reset_phrase') . ' exactly to confirm.'], 422);
        }
        if (!Hash::check($data['password'], (string) Auth::user()->password)) {
            return response()->json(['success' => false, 'message' => 'Your password is incorrect.'], 422);
        }

        // no backup, no reset
        try {
            $backup = $this->service->createBackup();
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'message' => 'Nothing was deleted because the safety backup could not be created.'], 500);
        }

        $this->audit('RESET ALL DATA started', ['backup' => $backup['name']]);
        try {
            $result = $this->reset->purge((bool) ($data['delete_files'] ?? false));
        } catch (\Throwable $e) {
            report($e);
            $this->audit('RESET ALL DATA failed', ['backup' => $backup['name'], 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'backup' => $backup['name'],
                'message' => 'The reset failed part-way. Your safety backup is ' . $backup['name'] . '.'], 500);
        }
        $this->audit('RESET ALL DATA finished', ['backup' => $backup['name'], 'rows' => $result['rows'], 'files' => $result['files']]);

        return response()->json([
            'success' => true,
            'message' => 'All data was reset. Admin and master admin accounts were kept.',
            'backup' => $backup,
            'data' => $result,
        ]);
    }
}
