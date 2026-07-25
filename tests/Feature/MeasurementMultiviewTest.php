<?php

namespace Tests\Feature;

use App\Models\Measurement;
use App\Models\User;
use App\Services\CVMeasurementService;
use App\Services\PhotoValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class MeasurementMultiviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_measurement_analysis_requires_front_side_and_back_photos(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post(route('user.measurement.analyze'), [
            'front_photo' => UploadedFile::fake()->image('front.jpg'),
            'ref_object' => 'aruco_a4',
        ]);

        $response->assertSessionHasErrors(['side_photo', 'back_photo']);
    }

    public function test_user_can_save_complete_multiview_measurement_result(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);

        $this->mock(CVMeasurementService::class, function ($mock): void {
            $mock->shouldReceive('measure')->once()->andReturn([
                'success' => true,
                'confidence' => 0.86,
                'quality_score' => 0.82,
                'ref_detected' => true,
                'per_field_confidence' => ['chest' => 0.78],
                'data' => [
                    'neck' => 38.2,
                    'chest' => 92.4,
                    'waist' => 78.2,
                    'hips' => 96.1,
                    'shoulder_width' => 44.0,
                    'shirt_length' => 68.5,
                    'arm_length' => 57.2,
                    'upper_arm' => 31.4,
                    'wrist' => 17.0,
                    'height' => 169.8,
                    'pants_waist' => 78.2,
                    'pants_hips' => 96.1,
                    'thigh' => 55.5,
                    'knee' => 38.1,
                    'calf' => 36.4,
                    'ankle' => 22.0,
                    'inseam' => 76.3,
                    'outseam' => 98.5,
                    'rise' => 22.2,
                ],
            ]);
        });

        $analysis = $this->actingAs($user)->post(route('user.measurement.analyze'), [
            'front_photo' => UploadedFile::fake()->image('front.jpg'),
            'side_photo' => UploadedFile::fake()->image('side.jpg'),
            'back_photo' => UploadedFile::fake()->image('back.jpg'),
            'ref_object' => 'aruco_a4',
        ]);

        $analysis->assertOk();
        $analysis->assertSee('Hasil Analisis Multi-view');

        $response = $this->actingAs($user)->post(route('user.measurement.store'), [
            'front_photo_path' => 'measurements/1/front.jpg',
            'side_photo_path' => 'measurements/1/side.jpg',
            'back_photo_path' => 'measurements/1/back.jpg',
            'ref_object' => 'aruco_a4',
            'confidence_score' => 0.86,
            'quality_score' => 0.82,
            'raw_cv_json' => json_encode(['success' => true]),
            'neck' => 38.2,
            'original_neck' => 38.2,
            'chest' => 93.0,
            'original_chest' => 92.4,
            'waist' => 78.2,
            'original_waist' => 78.2,
            'hips' => 96.1,
            'original_hips' => 96.1,
            'shoulder_width' => 44.0,
            'original_shoulder_width' => 44.0,
            'shirt_length' => 68.5,
            'original_shirt_length' => 68.5,
            'arm_length' => 57.2,
            'original_arm_length' => 57.2,
            'upper_arm' => 31.4,
            'original_upper_arm' => 31.4,
            'wrist' => 17.0,
            'original_wrist' => 17.0,
            'height' => 169.8,
            'original_height' => 169.8,
            'pants_waist' => 78.2,
            'original_pants_waist' => 78.2,
            'pants_hips' => 96.1,
            'original_pants_hips' => 96.1,
            'thigh' => 55.5,
            'original_thigh' => 55.5,
            'knee' => 38.1,
            'original_knee' => 38.1,
            'calf' => 36.4,
            'original_calf' => 36.4,
            'ankle' => 22.0,
            'original_ankle' => 22.0,
            'inseam' => 76.3,
            'original_inseam' => 76.3,
            'outseam' => 98.5,
            'original_outseam' => 98.5,
            'rise' => 22.2,
            'original_rise' => 22.2,
        ]);

        $response->assertRedirect(route('user.measurement.index'));
        $this->assertDatabaseHas('measurements', [
            'user_id' => $user->id,
            'measurement_method' => 'multiview_cv',
            'chest' => 93.0,
            'thigh' => 55.5,
        ]);

        $measurement = Measurement::firstOrFail();
        $this->assertArrayHasKey('chest', $measurement->edited_fields_json);
    }

    public function test_ktp_reference_uses_fixed_dimensions_without_manual_input(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);

        $this->mock(PhotoValidationService::class, function ($mock): void {
            $mock->shouldReceive('validate')->times(3)->andReturn([
                'valid' => true,
                'issues' => [],
                'suggestion' => '',
            ]);
        });

        $this->mock(CVMeasurementService::class, function ($mock): void {
            $mock->shouldReceive('measure')
                ->once()
                ->with(
                    Mockery::type(UploadedFile::class),
                    Mockery::type(UploadedFile::class),
                    Mockery::type(UploadedFile::class),
                    'ktp',
                    8.56,
                    5.398,
                )
                ->andReturn([
                    'success' => true,
                    'confidence' => 0.8,
                    'quality_score' => 0.78,
                    'ref_detected' => true,
                    'per_field_confidence' => [],
                    'data' => [
                        'neck' => 38,
                        'chest' => 92,
                        'waist' => 78,
                        'hips' => 96,
                        'shoulder_width' => 44,
                        'shirt_length' => 68,
                        'arm_length' => 57,
                        'upper_arm' => 31,
                        'wrist' => 17,
                        'height' => 170,
                        'pants_waist' => 78,
                        'pants_hips' => 96,
                        'thigh' => 55,
                        'knee' => 38,
                        'calf' => 36,
                        'ankle' => 22,
                        'inseam' => 76,
                        'outseam' => 98,
                        'rise' => 22,
                    ],
                ]);
        });

        $response = $this->actingAs($user)->post(route('user.measurement.analyze'), [
            'front_photo' => UploadedFile::fake()->image('front.jpg'),
            'side_photo' => UploadedFile::fake()->image('side.jpg'),
            'back_photo' => UploadedFile::fake()->image('back.jpg'),
            'ref_object' => 'ktp',
        ]);

        $response->assertOk();
        $response->assertViewHas('refObject', 'ktp');
        $response->assertViewHas('refWidthCm', 8.56);
        $response->assertViewHas('refHeightCm', 5.398);
        $response->assertViewHas('refSize', '8.56x5.398cm');
    }
}
