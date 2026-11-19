<?php

namespace Tests\Feature\Scholarship;

use App\Enums\Role;
use App\Models\ScholarshipCriterion;
use App\Models\ScholarshipWeightSet;
use App\Models\SchoolYear;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScholarshipWeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_weight_set_list(): void
    {
        $fixture = $this->createFixture();
        $this->createWeightSet($fixture, 'Bobot v1', ['C1' => 35, 'C2' => 15, 'C3' => 25, 'C4' => 10, 'C5' => 15]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.scholarship-weights.index'))
            ->assertOk()
            ->assertSee('Bobot Beasiswa')
            ->assertSee('Bobot v1')
            ->assertSee('100,00%');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.scholarship-weights.index'))->assertRedirect(route('login'));
    }

    public function test_kepala_sekolah_cannot_manage_scholarship_weights(): void
    {
        $this->createFixture();

        $this->actingAs($this->userWithRole(Role::KepalaSekolah))
            ->get(route('admin.scholarship-weights.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_weight_set_with_total_hundred_percent(): void
    {
        $fixture = $this->createFixture();

        $response = $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.scholarship-weights.store'), [
                'semester_id' => $fixture['semester']->id,
                'version_name' => 'Bobot v1',
                'weights' => $this->percentages($fixture, ['C1' => 35, 'C2' => 15, 'C3' => 25, 'C4' => 10, 'C5' => 15]),
            ]);

        $weightSet = ScholarshipWeightSet::where('version_name', 'Bobot v1')->firstOrFail();

        $response->assertRedirect(route('admin.scholarship-weights.show', $weightSet));
        $response->assertSessionHas('success');

        $this->assertFalse($weightSet->is_locked);
        $this->assertSame($fixture['semester']->id, $weightSet->semester_id);
        $this->assertSame($fixture['schoolYear']->id, $weightSet->school_year_id);
        $this->assertSame(5, DB::table('scholarship_weights')->where('weight_set_id', $weightSet->id)->count());

        // Bobot disimpan sebagai pecahan: 35% menjadi 0.35.
        $stored = (float) DB::table('scholarship_weights')
            ->where('weight_set_id', $weightSet->id)
            ->where('criterion_id', $fixture['criteria']['C1']->id)
            ->value('weight');

        $this->assertSame(0.35, $stored);
    }

    public function test_total_weight_must_be_hundred_percent(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.scholarship-weights.create'))
            ->post(route('admin.scholarship-weights.store'), [
                'semester_id' => $fixture['semester']->id,
                'version_name' => 'Bobot Tidak Seimbang',
                'weights' => $this->percentages($fixture, ['C1' => 35, 'C2' => 15, 'C3' => 25, 'C4' => 10, 'C5' => 5]),
            ])
            ->assertSessionHasErrors('weights');

        $this->assertDatabaseCount('scholarship_weight_sets', 0);
    }

    public function test_each_weight_is_limited_to_hundred(): void
    {
        $fixture = $this->createFixture();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.scholarship-weights.create'))
            ->post(route('admin.scholarship-weights.store'), [
                'semester_id' => $fixture['semester']->id,
                'version_name' => 'Bobot Salah',
                'weights' => $this->percentages($fixture, ['C1' => 120, 'C2' => 15, 'C3' => 25, 'C4' => 10, 'C5' => 15]),
            ])
            ->assertSessionHasErrors('weights.'.$fixture['criteria']['C1']->id);
    }

    public function test_weight_is_required_for_every_active_criterion(): void
    {
        $fixture = $this->createFixture();

        $weights = $this->percentages($fixture, ['C1' => 35, 'C2' => 15, 'C3' => 25, 'C4' => 10, 'C5' => 15]);
        unset($weights[$fixture['criteria']['C5']->id]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.scholarship-weights.create'))
            ->post(route('admin.scholarship-weights.store'), [
                'semester_id' => $fixture['semester']->id,
                'version_name' => 'Bobot Kurang',
                'weights' => $weights,
            ])
            ->assertSessionHasErrors('weights');
    }

    public function test_version_name_must_be_unique_per_semester(): void
    {
        $fixture = $this->createFixture();
        $this->createWeightSet($fixture, 'Bobot v1');

        $payload = [
            'semester_id' => $fixture['semester']->id,
            'version_name' => 'Bobot v1',
            'weights' => $this->percentages($fixture, ['C1' => 30, 'C2' => 20, 'C3' => 25, 'C4' => 10, 'C5' => 15]),
        ];

        $this->actingAs($this->userWithRole(Role::Admin))
            ->from(route('admin.scholarship-weights.create'))
            ->post(route('admin.scholarship-weights.store'), $payload)
            ->assertSessionHasErrors('version_name');

        // Nama versi yang sama masih boleh dipakai pada semester lain.
        $payload['semester_id'] = $fixture['pastSemester']->id;

        $this->actingAs($this->userWithRole(Role::Admin))
            ->post(route('admin.scholarship-weights.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame(2, ScholarshipWeightSet::where('version_name', 'Bobot v1')->count());
    }

    public function test_admin_can_update_weight_set(): void
    {
        $fixture = $this->createFixture();
        $weightSet = $this->createWeightSet($fixture, 'Bobot v1');

        $this->actingAs($this->userWithRole(Role::Admin))
            ->put(route('admin.scholarship-weights.update', $weightSet), [
                'semester_id' => $fixture['semester']->id,
                'version_name' => 'Bobot v1',
                'weights' => $this->percentages($fixture, ['C1' => 30, 'C2' => 20, 'C3' => 25, 'C4' => 10, 'C5' => 15]),
            ])
            ->assertRedirect(route('admin.scholarship-weights.show', $weightSet))
            ->assertSessionHasNoErrors();

        $stored = (float) DB::table('scholarship_weights')
            ->where('weight_set_id', $weightSet->id)
            ->where('criterion_id', $fixture['criteria']['C1']->id)
            ->value('weight');

        $this->assertSame(0.30, $stored);
        $this->assertSame(5, DB::table('scholarship_weights')->where('weight_set_id', $weightSet->id)->count());
    }

    public function test_locked_weight_set_cannot_be_edited(): void
    {
        $fixture = $this->createFixture();
        $weightSet = $this->createWeightSet($fixture, 'Bobot v1', null, ['is_locked' => true]);

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.scholarship-weights.edit', $weightSet))
            ->assertRedirect(route('admin.scholarship-weights.show', $weightSet))
            ->assertSessionHas('error');

        $this->actingAs($this->userWithRole(Role::Admin))
            ->put(route('admin.scholarship-weights.update', $weightSet), [
                'semester_id' => $fixture['semester']->id,
                'version_name' => 'Bobot v1',
                'weights' => $this->percentages($fixture, ['C1' => 50, 'C2' => 10, 'C3' => 20, 'C4' => 10, 'C5' => 10]),
            ])
            ->assertSessionHas('error');

        $stored = (float) DB::table('scholarship_weights')
            ->where('weight_set_id', $weightSet->id)
            ->where('criterion_id', $fixture['criteria']['C1']->id)
            ->value('weight');

        $this->assertSame(0.35, $stored);
    }

    public function test_admin_can_lock_and_unlock_unused_weight_set(): void
    {
        $fixture = $this->createFixture();
        $weightSet = $this->createWeightSet($fixture, 'Bobot v1');
        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->patch(route('admin.scholarship-weights.lock', $weightSet), ['is_locked' => 1])
            ->assertSessionHas('success');

        $this->assertTrue($weightSet->fresh()->is_locked);

        $this->actingAs($admin)
            ->patch(route('admin.scholarship-weights.lock', $weightSet), ['is_locked' => 0])
            ->assertSessionHas('success');

        $this->assertFalse($weightSet->fresh()->is_locked);
    }

    public function test_used_weight_set_cannot_be_unlocked_or_deleted(): void
    {
        $fixture = $this->createFixture();
        $weightSet = $this->createWeightSet($fixture, 'Bobot v1', null, ['is_locked' => true]);
        $this->createRankingRun($fixture, $weightSet);

        $admin = $this->userWithRole(Role::Admin);

        $this->actingAs($admin)
            ->patch(route('admin.scholarship-weights.lock', $weightSet), ['is_locked' => 0])
            ->assertSessionHas('error');

        $this->assertTrue($weightSet->fresh()->is_locked);

        $this->actingAs($admin)
            ->delete(route('admin.scholarship-weights.destroy', $weightSet))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('scholarship_weight_sets', ['id' => $weightSet->id]);
    }

    public function test_unused_and_unlocked_weight_set_can_be_deleted(): void
    {
        $fixture = $this->createFixture();
        $weightSet = $this->createWeightSet($fixture, 'Bobot v1');

        $this->actingAs($this->userWithRole(Role::Admin))
            ->delete(route('admin.scholarship-weights.destroy', $weightSet))
            ->assertRedirect(route('admin.scholarship-weights.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('scholarship_weight_sets', ['id' => $weightSet->id]);
        $this->assertSame(0, DB::table('scholarship_weights')->where('weight_set_id', $weightSet->id)->count());
    }

    public function test_create_form_can_copy_weights_from_existing_version(): void
    {
        $fixture = $this->createFixture();
        $weightSet = $this->createWeightSet($fixture, 'Bobot v1', null, ['is_locked' => true]);

        // Data Alpine ditulis Blade sebagai JSON.parse dengan tanda kutip ter-escape (\u0022).
        $escapedKey = '\u0022'.$fixture['criteria']['C1']->id.'\u0022:35';

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.scholarship-weights.create', ['from' => $weightSet->id]))
            ->assertOk()
            ->assertSee('Bobot disalin dari Bobot v1')
            ->assertSee($escapedKey, false);
    }

    public function test_show_page_lists_criteria_and_usage(): void
    {
        $fixture = $this->createFixture();
        $weightSet = $this->createWeightSet($fixture, 'Bobot v1', null, ['is_locked' => true]);
        $this->createRankingRun($fixture, $weightSet, 'published');

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get(route('admin.scholarship-weights.show', $weightSet))
            ->assertOk()
            ->assertSee('Rincian Bobot')
            ->assertSee('Nilai Akademik')
            ->assertSee('Penghasilan Orang Tua')
            ->assertSee('35,00%')
            ->assertSee('Riwayat Pemakaian')
            ->assertSee('Published');
    }

    /**
     * Fixture: tahun ajaran aktif + semester berjalan dan semester lampau,
     * serta lima kriteria beasiswa seperti data awal aplikasi.
     *
     * @return array<string, mixed>
     */
    private function createFixture(): array
    {
        $schoolYear = SchoolYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-13',
            'end_date' => '2027-06-11',
            'is_active' => true,
        ]);

        $semester = Semester::create([
            'school_year_id' => $schoolYear->id,
            'name' => 'Ganjil',
            'start_date' => '2026-07-13',
            'end_date' => '2026-12-18',
        ]);

        $pastSemester = Semester::create([
            'school_year_id' => $schoolYear->id,
            'name' => 'Genap',
            'start_date' => '2027-01-11',
            'end_date' => '2027-06-11',
        ]);

        $definitions = [
            'C1' => ['Nilai Akademik', 'numeric', 'benefit'],
            'C2' => ['Persentase Kehadiran', 'numeric', 'benefit'],
            'C3' => ['Penghasilan Orang Tua', 'numeric', 'cost'],
            'C4' => ['Jumlah Tanggungan Keluarga', 'numeric', 'cost'],
            'C5' => ['Prestasi Non-Akademik', 'ordinal', 'benefit'],
        ];

        $criteria = [];

        foreach ($definitions as $code => [$name, $type, $direction]) {
            $criteria[$code] = ScholarshipCriterion::create([
                'code' => $code,
                'name' => $name,
                'attribute_type' => $type,
                'direction' => $direction,
                'description' => 'Kriteria '.$code,
                'is_active' => true,
            ]);
        }

        return compact('schoolYear', 'semester', 'pastSemester', 'criteria');
    }

    /**
     * @param  array<string, int|float>  $percentages  persen per kode kriteria
     * @return array<int, int|float>
     */
    private function percentages(array $fixture, array $percentages): array
    {
        $weights = [];

        foreach ($percentages as $code => $percent) {
            $weights[$fixture['criteria'][$code]->id] = $percent;
        }

        return $weights;
    }

    /**
     * @param  array<string, int|float>|null  $percentages
     * @param  array<string, mixed>  $attributes
     */
    private function createWeightSet(array $fixture, string $version, ?array $percentages = null, array $attributes = []): ScholarshipWeightSet
    {
        $weightSet = ScholarshipWeightSet::create(array_merge([
            'school_year_id' => $fixture['schoolYear']->id,
            'semester_id' => $fixture['semester']->id,
            'version_name' => $version,
            'is_locked' => false,
            'created_by' => $this->userWithRole(Role::Admin)->id,
        ], $attributes));

        $sync = [];

        foreach ($percentages ?? ['C1' => 35, 'C2' => 15, 'C3' => 25, 'C4' => 10, 'C5' => 15] as $code => $percent) {
            $sync[$fixture['criteria'][$code]->id] = ['weight' => $percent / 100];
        }

        $weightSet->criteria()->sync($sync);

        return $weightSet;
    }

    private function createRankingRun(array $fixture, ScholarshipWeightSet $weightSet, string $status = 'draft'): void
    {
        DB::table('scholarship_ranking_runs')->insert([
            'school_year_id' => $fixture['schoolYear']->id,
            'semester_id' => $fixture['semester']->id,
            'weight_set_id' => $weightSet->id,
            'calculated_at' => now(),
            'status' => $status,
            'calculated_by' => $this->userWithRole(Role::Admin)->id,
            'created_at' => now(),
        ]);
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'role' => $role,
            'is_active' => true,
        ])->save();

        return $user;
    }
}
