<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\MeasurementAccuracyService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('measurement:evaluate {csv}', function (string $csv) {
    $service = app(MeasurementAccuracyService::class);
    $path = preg_match('/^[A-Za-z]:[\/\\\\]/', $csv) ? $csv : base_path($csv);
    $result = $service->evaluateCsv($path);

    $this->info("Dataset rows: {$result['rows']}");
    $this->table(
        ['Ukuran', 'Sample', 'MAE (cm)', 'Max Error (cm)'],
        collect($result['metrics'])->map(fn (array $metric, string $field): array => [
            $field,
            $metric['samples'],
            $metric['mae_cm'],
            $metric['max_error_cm'],
        ])->values()->all()
    );
})->purpose('Evaluate measurement prediction accuracy against manual tailoring measurements');
