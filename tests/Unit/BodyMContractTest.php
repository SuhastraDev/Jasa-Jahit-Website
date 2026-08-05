<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

final class BodyMContractTest extends TestCase
{
    private const FIELDS = [
        'ankle_girth',
        'arm_length',
        'bicep_girth',
        'calf_girth',
        'chest_girth',
        'forearm_girth',
        'height',
        'hip_girth',
        'leg_length',
        'shoulder_breadth',
        'shoulder_to_crotch',
        'thigh_girth',
        'waist_girth',
        'wrist_girth',
    ];

    public function test_bodym_is_disabled_until_a_trained_model_is_available(): void
    {
        $this->assertFalse(config('bodym.enabled'));
        $this->assertSame('untrained', config('bodym.model_version'));
        $this->assertSame('multiview_cv', config('bodym.legacy_method'));
        $this->assertSame('bodym_ml', config('bodym.method'));
    }

    public function test_laravel_and_json_schema_freeze_the_same_fourteen_fields(): void
    {
        $schema = json_decode(
            file_get_contents(base_path('docs/bodym/measurement-contract-v1.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('bodym.v1', config('bodym.contract_version'));
        $this->assertSame(self::FIELDS, array_keys(config('bodym.measurements')));
        $this->assertSame(self::FIELDS, $schema['properties']['data']['required']);
        $this->assertSame(self::FIELDS, array_keys($schema['properties']['data']['properties']));
        $this->assertSame(self::FIELDS, $schema['properties']['per_field_confidence']['required']);
        $this->assertFalse($schema['properties']['data']['additionalProperties']);
        $this->assertCount(14, config('bodym.measurements'));
    }

    public function test_every_bodym_measurement_has_a_supported_type_and_centimeter_unit(): void
    {
        $supportedTypes = ['circumference', 'length', 'breadth', 'height'];

        foreach (config('bodym.measurements') as $field => $metadata) {
            $this->assertContains($metadata['type'], $supportedTypes, $field);
            $this->assertSame('cm', $metadata['unit'], $field);
            $this->assertNotSame('', $metadata['label'], $field);
        }
    }
}
