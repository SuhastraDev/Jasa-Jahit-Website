<?php

namespace App\Services;

class MeasurementAccuracyService
{
    public const FIELDS = [
        'neck',
        'chest',
        'waist',
        'hips',
        'shoulder_width',
        'shirt_length',
        'arm_length',
        'upper_arm',
        'wrist',
        'height',
        'pants_waist',
        'pants_hips',
        'thigh',
        'knee',
        'calf',
        'ankle',
        'inseam',
        'outseam',
        'rise',
    ];

    public function evaluateCsv(string $path): array
    {
        if (!is_readable($path)) {
            throw new \InvalidArgumentException("File dataset tidak dapat dibaca: {$path}");
        }

        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle);
        if (!$headers) {
            throw new \InvalidArgumentException('Dataset CSV kosong atau header tidak valid.');
        }

        $headers = array_map('trim', $headers);
        $rows = 0;
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows++;
            $record = array_combine($headers, $row);
            if (!is_array($record)) {
                continue;
            }

            foreach (self::FIELDS as $field) {
                $predictedKey = "predicted_{$field}";
                $actualKey = "actual_{$field}";
                if (!isset($record[$predictedKey], $record[$actualKey]) || $record[$predictedKey] === '' || $record[$actualKey] === '') {
                    continue;
                }

                $errors[$field][] = abs((float) $record[$predictedKey] - (float) $record[$actualKey]);
            }
        }

        fclose($handle);

        $metrics = [];
        foreach ($errors as $field => $values) {
            $metrics[$field] = [
                'samples' => count($values),
                'mae_cm' => round(array_sum($values) / max(1, count($values)), 2),
                'max_error_cm' => round(max($values), 2),
            ];
        }

        return [
            'rows' => $rows,
            'metrics' => $metrics,
        ];
    }
}
