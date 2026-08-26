<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GarmentAnalysisMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_refreshing_analysis_url_redirects_to_garment_form_instead_of_405(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->get(route('user.measurement.garment-analyze.get'));

        $response
            ->assertRedirect(route('user.measurement.garment-index'))
            ->assertSessionHas('error', 'Halaman analisis sudah tidak aktif. Silakan unggah foto pakaian kembali.');
    }
}
