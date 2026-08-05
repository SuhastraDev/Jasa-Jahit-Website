<?php

namespace Tests\Feature;

use App\Models\Measurement;
use App\Models\User;
use App\Services\CVMeasurementService;
use App\Services\PhotoValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class MeasurementMultiviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_load_local_pose_detector_assets(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('user.measurement.pose-model'))
            ->assertOk()
            ->assertHeader('content-type', 'application/octet-stream');

        $this->actingAs($user)
            ->get(route('user.measurement.mediapipe-asset', ['file' => 'vision_wasm_internal.wasm']))
            ->assertOk()
            ->assertHeader('content-type', 'application/wasm');
    }

    public function test_pose_detector_asset_route_rejects_unknown_files(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('user.measurement.mediapipe-asset', ['file' => 'unknown.js']))
            ->assertNotFound();
    }

    public function test_measurement_analysis_requires_front_side_and_back_photos(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post(route('user.measurement.analyze'), [
            'front_photo' => UploadedFile::fake()->image('front.jpg'),
            'ref_object' => 'a4',
        ]);

        $response->assertSessionHasErrors(['side_photo', 'back_photo']);
    }

    public function test_measurement_analysis_shows_clear_error_when_photo_is_too_large(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->mock(CVMeasurementService::class, function ($mock): void {
            $mock->shouldNotReceive('measure');
        });

        $response = $this->actingAs($user)->post(route('user.measurement.analyze'), [
            'front_photo' => UploadedFile::fake()->image('front.jpg')->size(6000),
            'side_photo' => UploadedFile::fake()->image('side.jpg'),
            'back_photo' => UploadedFile::fake()->image('back.jpg'),
            'ref_object' => 'a4',
            'reference_mode' => 'fixed',
        ]);

        $response->assertSessionHasErrors([
            'front_photo' => 'Foto depan terlalu besar. Maksimal 5MB per foto.',
        ]);
    }

    public function test_user_can_start_background_measurement_analysis(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);

        $this->mock(PhotoValidationService::class, function ($mock): void {
            $mock->shouldReceive('validateMany')->once()->andReturn([
                'front_photo' => ['valid' => true, 'issues' => []],
                'side_photo' => ['valid' => true, 'issues' => []],
                'back_photo' => ['valid' => true, 'issues' => []],
            ]);
        });
        $this->mock(CVMeasurementService::class, function ($mock): void {
            $mock->shouldReceive('startMeasurementJob')->once()->andReturn([
                'success' => true,
                'job_id' => 'job-mobile-123',
            ]);
        });

        $response = $this->actingAs($user)->post(
            route('user.measurement.analysis-start'),
            [
                'front_photo' => UploadedFile::fake()->image('front.jpg'),
                'side_photo' => UploadedFile::fake()->image('side.jpg'),
                'back_photo' => UploadedFile::fake()->image('back.jpg'),
                'ref_object' => 'a4',
                'reference_mode' => 'fixed',
                'front_reference_box' => '{"x":10,"y":10,"w":30,"h":42,"image_width":200,"image_height":300}',
                'side_reference_box' => '{"x":10,"y":10,"w":30,"h":42,"image_width":200,"image_height":300}',
                'back_reference_box' => '{"x":10,"y":10,"w":30,"h":42,"image_width":200,"image_height":300}',
            ],
            ['Accept' => 'application/json'],
        );

        $response
            ->assertAccepted()
            ->assertJsonPath('job_id', 'job-mobile-123')
            ->assertJsonStructure(['status_url']);
        $this->assertTrue(Cache::has("measurement_analysis:{$user->id}:job-mobile-123"));
    }

    public function test_background_analysis_status_exposes_real_progress_and_result_page(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $jobId = 'job-completed-456';
        Cache::put("measurement_analysis:{$user->id}:{$jobId}", [
            'user_id' => $user->id,
            'front_photo_path' => 'measurements/1/front.jpg',
            'side_photo_path' => 'measurements/1/side.jpg',
            'back_photo_path' => 'measurements/1/back.jpg',
            'ref_object' => 'a4',
            'ref_width_cm' => 21.0,
            'ref_height_cm' => 29.7,
            'reference_mode' => 'fixed',
            'result' => null,
        ], now()->addMinutes(10));

        $this->mock(CVMeasurementService::class, function ($mock): void {
            $mock->shouldReceive('measurementJobStatus')->once()->andReturn([
                'status' => 'completed',
                'progress' => [
                    'stage' => 'completed',
                    'percent' => 100,
                    'message' => 'Analisis selesai',
                    'view' => null,
                ],
                'result' => [
                    'success' => true,
                    'confidence' => 0.86,
                    'quality_score' => 0.84,
                    'ref_detected' => true,
                    'per_field_confidence' => ['chest' => 0.82],
                    'data' => ['chest' => 92.4],
                ],
            ]);
        });

        $status = $this->actingAs($user)->getJson(
            route('user.measurement.analysis-status', $jobId),
        );

        $status
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('progress.percent', 100)
            ->assertJsonStructure(['result_url'])
            ->assertJsonMissing(['result']);

        $this->actingAs($user)
            ->get(route('user.measurement.analysis-result', $jobId))
            ->assertOk()
            ->assertSee('Hasil Analisis Multi-view')
            ->assertSee('92.4');
    }

    public function test_bodym_result_page_only_shows_bodym_contract_fields(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $jobId = 'job-bodym-contract-123';

        Cache::put("measurement_analysis:{$user->id}:{$jobId}", [
            'user_id' => $user->id,
            'front_photo_path' => 'measurements/1/front.jpg',
            'side_photo_path' => 'measurements/1/side.jpg',
            'back_photo_path' => 'measurements/1/back.jpg',
            'ref_object' => 'a4',
            'ref_width_cm' => 21.0,
            'ref_height_cm' => 29.7,
            'reference_mode' => 'fixed',
            'result' => [
                'success' => true,
                'measurement_method' => 'bodym_ml',
                'confidence' => 0.89,
                'quality_score' => 0.87,
                'ref_detected' => true,
                'bodym_contract_version' => 'bodym.v1',
                'bodym_model_version' => 'bodym-v1',
                'bodym_status' => 'ok',
                'bodym_data' => [
                    'ankle_girth' => 22.0,
                    'arm_length' => 57.2,
                    'bicep_girth' => 31.4,
                    'calf_girth' => 36.4,
                    'chest_girth' => 92.4,
                    'forearm_girth' => 25.0,
                    'height' => 169.8,
                    'hip_girth' => 96.1,
                    'leg_length' => 94.0,
                    'shoulder_breadth' => 44.0,
                    'shoulder_to_crotch' => 63.0,
                    'thigh_girth' => 55.5,
                    'waist_girth' => 78.2,
                    'wrist_girth' => 17.0,
                ],
                'bodym_per_field_confidence' => ['height' => 0.95],
                'bodym_prediction_intervals_cm' => ['height' => [168.0, 171.0]],
                'data' => [
                    'chest' => 92.4,
                    'shirt_length' => 68.5,
                    'pants_waist' => 78.2,
                ],
            ],
        ], now()->addMinutes(10));

        $this->actingAs($user)
            ->get(route('user.measurement.analysis-result', $jobId))
            ->assertOk()
            ->assertSee('Indikator BodyM Resmi')
            ->assertSee('LINGKAR')
            ->assertSee('LEBAR')
            ->assertSee('PANJANG')
            ->assertSee('TINGGI')
            ->assertSee('Tinggi badan')
            ->assertSee('Lingkar lengan bawah')
            ->assertDontSee('Ukuran Baju')
            ->assertDontSee('Ukuran Celana')
            ->assertDontSee('Panjang Baju')
            ->assertDontSee('Panjang Inseam');
    }

    public function test_failed_background_analysis_keeps_the_problematic_photo_detail(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $jobId = 'job-failed-front-789';
        Cache::put("measurement_analysis:{$user->id}:{$jobId}", [
            'user_id' => $user->id,
            'front_photo_path' => 'measurements/1/front.jpg',
            'side_photo_path' => 'measurements/1/side.jpg',
            'back_photo_path' => 'measurements/1/back.jpg',
            'ref_object' => 'a4',
            'ref_width_cm' => 21.0,
            'ref_height_cm' => 29.7,
            'reference_mode' => 'fixed',
            'result' => null,
        ], now()->addMinutes(10));

        $this->mock(CVMeasurementService::class, function ($mock): void {
            $mock->shouldReceive('measurementJobStatus')->once()->andReturn([
                'status' => 'failed',
                'error' => 'Kotak A4/KTP tidak mengikuti benda patokan.',
                'result' => [
                    'success' => false,
                    'failed_view' => 'front',
                    'failed_reason' => 'invalid_reference_scale',
                    'estimated_stature_cm' => 104.8,
                    'reference_processing' => [
                        'method' => 'manual_box',
                        'refined' => false,
                    ],
                ],
            ]);
        });

        $this->actingAs($user)
            ->getJson(route('user.measurement.analysis-status', $jobId))
            ->assertOk()
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('result.failed_view', 'front')
            ->assertJsonPath('result.failed_reason', 'invalid_reference_scale')
            ->assertJsonPath('result.reference_processing.refined', false);
    }

    public function test_handheld_reference_mode_is_rejected_for_ktp(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->mock(CVMeasurementService::class, function ($mock): void {
            $mock->shouldNotReceive('measure');
        });

        $response = $this->actingAs($user)->post(route('user.measurement.analyze'), [
            'front_photo' => UploadedFile::fake()->image('front.jpg'),
            'side_photo' => UploadedFile::fake()->image('side.jpg'),
            'back_photo' => UploadedFile::fake()->image('back.jpg'),
            'ref_object' => 'ktp',
            'reference_mode' => 'handheld',
        ]);

        $response->assertSessionHasErrors([
            'reference_mode' => 'Mode praktis hanya tersedia untuk A4. KTP harus ditempel atau disandarkan.',
        ]);
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
            'ref_object' => 'a4',
            'reference_mode' => 'fixed',
        ]);

        $analysis->assertOk();
        $analysis->assertSee('Hasil Analisis Multi-view');
        $analysis->assertSee('Lingkar Dada');
        $analysis->assertSee('Lebar Bahu');
        $analysis->assertSee('Panjang Lengan');
        $analysis->assertSee('Jenis ukuran');
        $analysis->assertSee('LINGKAR');
        $analysis->assertSee('LEBAR');
        $analysis->assertSee('PANJANG');

        $response = $this->actingAs($user)->post(route('user.measurement.store'), [
            'front_photo_path' => 'measurements/1/front.jpg',
            'side_photo_path' => 'measurements/1/side.jpg',
            'back_photo_path' => 'measurements/1/back.jpg',
            'ref_object' => 'a4',
            'reference_mode' => 'fixed',
            'confidence_score' => 0.86,
            'quality_score' => 0.82,
            'raw_cv_json' => json_encode(['success' => true, 'measurement_method' => 'bodym_ml']),
            'bodym_contract_version' => 'bodym.v1',
            'bodym_response_contract_version' => 'bodym-response.v1',
            'bodym_model_version' => 'bodym-v1',
            'bodym_status' => 'ok',
            'bodym_data_json' => json_encode([
                'ankle_girth' => 22.0,
                'arm_length' => 57.2,
                'bicep_girth' => 31.4,
                'calf_girth' => 36.4,
                'chest_girth' => 92.4,
                'forearm_girth' => 25.0,
                'height' => 169.8,
                'hip_girth' => 96.1,
                'leg_length' => 94.0,
                'shoulder_breadth' => 44.0,
                'shoulder_to_crotch' => 63.0,
                'thigh_girth' => 55.5,
                'waist_girth' => 78.2,
                'wrist_girth' => 17.0,
            ]),
            'bodym_per_field_confidence_json' => json_encode(['chest_girth' => 0.91]),
            'bodym_prediction_intervals_cm_json' => json_encode(['chest_girth' => [90.4, 94.4]]),
            'bodym_diagnostics_json' => json_encode(['legacy_fallback_fields' => ['shirt_length']]),
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
            'bodym_chest_girth' => 93.0,
            'original_bodym_chest_girth' => 92.4,
            'bodym_waist_girth' => 79.2,
            'original_bodym_waist_girth' => 78.2,
            'bodym_leg_length' => 94.0,
            'original_bodym_leg_length' => 94.0,
        ]);

        $response->assertRedirect(route('user.measurement.index'));
        $this->assertDatabaseHas('measurements', [
            'user_id' => $user->id,
            'measurement_method' => 'bodym_ml',
            'reference_mode' => 'fixed',
            'bodym_contract_version' => 'bodym.v1',
            'bodym_response_contract_version' => 'bodym-response.v1',
            'bodym_model_version' => 'bodym-v1',
            'bodym_status' => 'ok',
            'chest' => 93.0,
            'waist' => 79.2,
            'pants_waist' => 79.2,
            'thigh' => 55.5,
            'bodym_chest_girth' => 93.0,
            'bodym_waist_girth' => 79.2,
            'bodym_leg_length' => 94.0,
        ]);

        $measurement = Measurement::firstOrFail();
        $this->assertArrayHasKey('chest', $measurement->edited_fields_json);
        $this->assertArrayHasKey('bodym_chest_girth', $measurement->edited_fields_json);
        $this->assertSame(92.4, $measurement->bodym_data['chest_girth']);
        $this->assertSame(0.91, $measurement->bodym_per_field_confidence['chest_girth']);
        $this->assertSame([90.4, 94.4], $measurement->bodym_prediction_intervals_cm['chest_girth']);
    }

    public function test_ktp_reference_uses_fixed_dimensions_without_manual_input(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);

        $this->mock(PhotoValidationService::class, function ($mock): void {
            $mock->shouldReceive('validateMany')->once()->andReturn([
                'front_photo' => ['valid' => true, 'issues' => [], 'suggestion' => ''],
                'side_photo' => ['valid' => true, 'issues' => [], 'suggestion' => ''],
                'back_photo' => ['valid' => true, 'issues' => [], 'suggestion' => ''],
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
                    'fixed',
                    Mockery::type('array'),
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
            'reference_mode' => 'fixed',
        ]);

        $response->assertOk();
        $response->assertViewHas('refObject', 'ktp');
        $response->assertViewHas('refWidthCm', 8.56);
        $response->assertViewHas('refHeightCm', 5.398);
        $response->assertViewHas('refSize', '8.56x5.398cm');
    }

    public function test_handheld_a4_reference_mode_lowers_confidence_and_is_passed_to_cv(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);

        $this->mock(PhotoValidationService::class, function ($mock): void {
            $mock->shouldReceive('validateMany')->once()->andReturn([
                'front_photo' => ['valid' => true, 'issues' => [], 'suggestion' => ''],
                'side_photo' => ['valid' => true, 'issues' => [], 'suggestion' => ''],
                'back_photo' => ['valid' => true, 'issues' => [], 'suggestion' => ''],
            ]);
        });

        $this->mock(CVMeasurementService::class, function ($mock): void {
            $mock->shouldReceive('measure')
                ->once()
                ->with(
                    Mockery::type(UploadedFile::class),
                    Mockery::type(UploadedFile::class),
                    Mockery::type(UploadedFile::class),
                    'a4',
                    21.0,
                    29.7,
                    'handheld',
                    Mockery::type('array'),
                )
                ->andReturn([
                    'success' => true,
                    'confidence' => 0.8,
                    'quality_score' => 0.8,
                    'ref_detected' => true,
                    'per_field_confidence' => ['chest' => 0.8],
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
            'ref_object' => 'a4',
            'reference_mode' => 'handheld',
        ]);

        $response->assertOk();
        $response->assertViewHas('referenceMode', 'handheld');
        $response->assertViewHas('confidence', 0.72);
        $response->assertViewHas('qualityScore', 0.76);
    }

    public function test_manual_reference_boxes_are_passed_to_cv_service(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);
        $frontBox = '{"x":30,"y":40,"w":80,"h":120,"image_width":520,"image_height":390}';

        $this->mock(PhotoValidationService::class, function ($mock): void {
            $mock->shouldReceive('validateMany')->once()->andReturn([
                'front_photo' => ['valid' => true, 'issues' => [], 'suggestion' => ''],
                'side_photo' => ['valid' => true, 'issues' => [], 'suggestion' => ''],
                'back_photo' => ['valid' => true, 'issues' => [], 'suggestion' => ''],
            ]);
        });

        $this->mock(CVMeasurementService::class, function ($mock) use ($frontBox): void {
            $mock->shouldReceive('measure')
                ->once()
                ->with(
                    Mockery::type(UploadedFile::class),
                    Mockery::type(UploadedFile::class),
                    Mockery::type(UploadedFile::class),
                    'a4',
                    21.0,
                    29.7,
                    'fixed',
                    Mockery::on(fn ($boxes) => ($boxes['front'] ?? null) === $frontBox),
                )
                ->andReturn([
                    'success' => true,
                    'confidence' => 0.8,
                    'quality_score' => 0.8,
                    'ref_detected' => true,
                    'per_field_confidence' => [],
                    'data' => array_fill_keys([
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
                    ], 50),
                ]);
        });

        $response = $this->actingAs($user)->post(route('user.measurement.analyze'), [
            'front_photo' => UploadedFile::fake()->image('front.jpg'),
            'side_photo' => UploadedFile::fake()->image('side.jpg'),
            'back_photo' => UploadedFile::fake()->image('back.jpg'),
            'ref_object' => 'a4',
            'reference_mode' => 'fixed',
            'front_reference_box' => $frontBox,
        ]);

        $response->assertOk();
    }
}
