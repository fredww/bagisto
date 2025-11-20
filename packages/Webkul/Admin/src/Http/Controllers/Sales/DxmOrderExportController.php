<?php

namespace Webkul\Admin\Http\Controllers\Sales;

use App\Models\DxmOrderExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Jobs\DxmOrderExportJob;

class DxmOrderExportController extends Controller
{
    public function start(): JsonResponse
    {
        $date = request()->input('date');

        if (! $date) {
            $date = now()->toDateString();
        }

        $task = DxmOrderExport::create([
            'export_date' => $date,
            'state'       => 'pending',
        ]);

        $task->update(['state' => 'queued', 'started_at' => now()]);

        $storeAccount = trim((string) request()->input('storeAccount', '')) ?: null;

        Bus::dispatch(new DxmOrderExportJob($task->id, $storeAccount));

        return response()->json(['id' => $task->id]);
    }

    public function status(int $id): JsonResponse
    {
        $task = DxmOrderExport::findOrFail($id);

        return response()->json([
            'state'          => $task->state,
            'totalRows'      => (int) $task->total_rows,
            'processedRows'  => (int) $task->processed_rows,
            'downloadUrl'    => $task->file_path ? route('admin.sales.orders.dxm_export.download', $task->id) : null,
            'errorMessage'   => $task->error_message,
        ]);
    }

    public function download(int $id)
    {
        $task = DxmOrderExport::findOrFail($id);

        if (! $task->file_path || ! Storage::disk('local')->exists($task->file_path)) {
            return response()->json(['message' => 'File not ready'], Response::HTTP_NOT_FOUND);
        }

        $filename = basename($task->file_path);

        return response()->streamDownload(function () use ($task) {
            echo Storage::disk('local')->get($task->file_path);
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}