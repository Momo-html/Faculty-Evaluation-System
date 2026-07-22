<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CsvImportRequest;
use App\Services\ActivityLogger;
use App\Services\CsvUserImportService;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

class CsvImportController extends Controller
{
    public function __invoke(CsvImportRequest $request, CsvUserImportService $importer, ActivityLogger $logger): RedirectResponse
    {
        try {
            $log = $importer->import($request->file('csv_file'), $request->string('type')->toString(), $request->user()?->id);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['csv_file' => $exception->getMessage()]);
        }
        $logger->log($request, 'CSV_IMPORT', "Imported {$log->import_type}: {$log->successful_rows} successful, {$log->failed_rows} failed.");
        $message = "Import finished: {$log->successful_rows} created, {$log->failed_rows} rejected.";

        return back()->with($log->failed_rows ? 'warning' : 'success', $message)->with('import_errors', $log->errors->take(20));
    }
}
