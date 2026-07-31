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
        $files = $request->file('csv_files', []);
        if ($request->hasFile('csv_file')) {
            $files[] = $request->file('csv_file');
        }

        $successful = 0;
        $failed = 0;
        $importErrors = collect();
        $fileErrors = [];
        foreach ($files as $file) {
            try {
                $log = $importer->import($file, $request->string('type')->toString(), $request->user()?->id);
                $successful += $log->successful_rows;
                $failed += $log->failed_rows;
                $importErrors = $importErrors->concat($log->errors);
            } catch (RuntimeException $exception) {
                $fileErrors[] = "{$file->getClientOriginalName()}: {$exception->getMessage()}";
            }
        }

        if ($fileErrors !== []) {
            return back()->withErrors(['csv_files' => implode(' ', $fileErrors)]);
        }

        $logger->log($request, 'CSV_IMPORT', "Imported ".count($files)." CSV file(s): {$successful} successful, {$failed} failed.");
        $message = "Import finished: ".count($files)." file(s), {$successful} saved, {$failed} rejected.";

        return back()->with($failed ? 'warning' : 'success', $message)->with('import_errors', $importErrors->take(20));
    }
}
