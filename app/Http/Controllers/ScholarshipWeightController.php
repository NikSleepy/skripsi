<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScholarshipWeightSetRequest;
use App\Models\ScholarshipCriterion;
use App\Models\ScholarshipWeightSet;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Versi bobot beasiswa per periode beserta penguncian setelah dipakai.
 */
class ScholarshipWeightController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search')),
            'semester' => $request->query('semester'),
            'status' => $request->query('status'),
        ];

        $weightSets = ScholarshipWeightSet::query()
            ->with(['schoolYear', 'semester', 'criteria'])
            ->when($filters['search'] !== '', fn ($query) => $query->where('version_name', 'like', '%'.$filters['search'].'%'))
            ->when(filled($filters['semester']), fn ($query) => $query->where('semester_id', $filters['semester']))
            ->when($filters['status'] === 'terkunci', fn ($query) => $query->where('is_locked', true))
            ->when($filters['status'] === 'terbuka', fn ($query) => $query->where('is_locked', false))
            ->orderByDesc('semester_id')
            ->orderBy('version_name')
            ->paginate(10)
            ->withQueryString();

        return view('scholarship_weights.index', [
            'weightSets' => $weightSets,
            'filters' => $filters,
            'semesters' => $this->semesterOptions(),
            'criteriaCount' => ScholarshipCriterion::query()->where('is_active', true)->count(),
            'usageCounts' => $this->usageCounts($weightSets->pluck('id')),
            'totalSets' => ScholarshipWeightSet::query()->count(),
            'lockedSets' => ScholarshipWeightSet::query()->where('is_locked', true)->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $template = $request->integer('from') > 0
            ? ScholarshipWeightSet::query()->with(['criteria', 'semester'])->find($request->integer('from'))
            : null;

        return view('scholarship_weights.create', [
            'weightSet' => null,
            'template' => $template,
            'criteria' => $this->activeCriteria(),
            'semesters' => $this->semesterOptions(),
        ]);
    }

    public function store(ScholarshipWeightSetRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $semester = Semester::query()->findOrFail($data['semester_id']);

        $weightSet = ScholarshipWeightSet::create([
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->id,
            'version_name' => $data['version_name'],
            'is_locked' => false,
            'created_by' => $request->user()->id,
        ]);

        $this->syncWeights($weightSet, (array) $data['weights']);

        return redirect()
            ->route('admin.scholarship-weights.show', $weightSet)
            ->with('success', "Versi bobot {$weightSet->version_name} berhasil disimpan. Total bobot 100%.");
    }

    public function show(ScholarshipWeightSet $weightSet): View
    {
        $weightSet->load(['schoolYear', 'semester', 'criteria', 'creator']);

        return view('scholarship_weights.show', [
            'weightSet' => $weightSet,
            'usage' => $this->usage($weightSet->id),
            'rankingRunsCount' => $weightSet->rankingRunsCount(),
        ]);
    }

    public function edit(ScholarshipWeightSet $weightSet): View|RedirectResponse
    {
        if ($weightSet->is_locked) {
            return redirect()
                ->route('admin.scholarship-weights.show', $weightSet)
                ->with('error', "Versi bobot {$weightSet->version_name} terkunci sehingga tidak dapat diubah. Buat versi baru bila kebijakan bobot berubah.");
        }

        $weightSet->load(['criteria', 'semester', 'schoolYear']);

        return view('scholarship_weights.edit', [
            'weightSet' => $weightSet,
            'template' => null,
            'criteria' => $this->activeCriteria(),
            'semesters' => $this->semesterOptions(),
        ]);
    }

    public function update(ScholarshipWeightSetRequest $request, ScholarshipWeightSet $weightSet): RedirectResponse
    {
        if ($weightSet->is_locked) {
            return redirect()
                ->route('admin.scholarship-weights.show', $weightSet)
                ->with('error', "Versi bobot {$weightSet->version_name} terkunci sehingga tidak dapat diubah.");
        }

        $data = $request->validated();
        $semester = Semester::query()->findOrFail($data['semester_id']);

        $weightSet->update([
            'school_year_id' => $semester->school_year_id,
            'semester_id' => $semester->id,
            'version_name' => $data['version_name'],
        ]);

        $this->syncWeights($weightSet, (array) $data['weights']);

        return redirect()
            ->route('admin.scholarship-weights.show', $weightSet)
            ->with('success', "Versi bobot {$weightSet->version_name} berhasil diperbarui. Total bobot 100%.");
    }

    /**
     * Kunci versi bobot agar tidak berubah setelah dipakai. Membuka kunci hanya
     * boleh untuk versi yang belum pernah dipakai pada proses ranking.
     */
    public function updateLock(Request $request, ScholarshipWeightSet $weightSet): RedirectResponse
    {
        $request->validate(['is_locked' => ['required', 'boolean']]);

        $isLocked = $request->boolean('is_locked');

        if (! $isLocked && $weightSet->rankingRunsCount() > 0) {
            return back()->with(
                'error',
                "Versi bobot {$weightSet->version_name} sudah dipakai pada proses ranking sehingga kuncinya tidak dapat dibuka. Buat versi baru bila bobot perlu diubah."
            );
        }

        $weightSet->update(['is_locked' => $isLocked]);

        return back()->with('success', $isLocked
            ? "Versi bobot {$weightSet->version_name} dikunci dan tidak dapat diubah lagi."
            : "Kunci versi bobot {$weightSet->version_name} dibuka.");
    }

    /**
     * Hapus versi bobot yang masih terbuka dan belum pernah dipakai.
     */
    public function destroy(ScholarshipWeightSet $weightSet): RedirectResponse
    {
        if ($weightSet->is_locked) {
            return back()->with('error', "Versi bobot {$weightSet->version_name} terkunci sehingga tidak dapat dihapus.");
        }

        if ($weightSet->rankingRunsCount() > 0) {
            return back()->with(
                'error',
                "Versi bobot {$weightSet->version_name} sudah dipakai pada proses ranking sehingga tidak dapat dihapus."
            );
        }

        $name = $weightSet->version_name;
        $weightSet->delete();

        return redirect()
            ->route('admin.scholarship-weights.index')
            ->with('success', "Versi bobot {$name} dihapus.");
    }

    /**
     * Simpan bobot dalam bentuk pecahan (35% menjadi 0.35).
     *
     * @param  array<int|string, mixed>  $weights
     */
    private function syncWeights(ScholarshipWeightSet $weightSet, array $weights): void
    {
        $sync = [];

        foreach ($weights as $criterionId => $percent) {
            $sync[(int) $criterionId] = ['weight' => round(((float) $percent) / 100, 6)];
        }

        $weightSet->criteria()->sync($sync);
    }

    /**
     * @return Collection<int, ScholarshipCriterion>
     */
    private function activeCriteria(): Collection
    {
        return ScholarshipCriterion::query()->where('is_active', true)->orderBy('code')->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function semesterOptions(): Collection
    {
        return Semester::query()->with('schoolYear')->orderByDesc('start_date')->get();
    }

    /**
     * @param  Collection<int, int>  $weightSetIds
     * @return Collection<int|string, int>
     */
    private function usageCounts(Collection $weightSetIds): Collection
    {
        if ($weightSetIds->isEmpty()) {
            return collect();
        }

        return DB::table('scholarship_ranking_runs')
            ->whereIn('weight_set_id', $weightSetIds)
            ->groupBy('weight_set_id')
            ->selectRaw('weight_set_id, COUNT(*) as total')
            ->pluck('total', 'weight_set_id');
    }

    /**
     * @return Collection<int, object>
     */
    private function usage(int $weightSetId): Collection
    {
        return DB::table('scholarship_ranking_runs')
            ->join('school_years', 'school_years.id', '=', 'scholarship_ranking_runs.school_year_id')
            ->join('semesters', 'semesters.id', '=', 'scholarship_ranking_runs.semester_id')
            ->where('scholarship_ranking_runs.weight_set_id', $weightSetId)
            ->orderByDesc('scholarship_ranking_runs.calculated_at')
            ->get([
                'school_years.name as school_year',
                'semesters.name as semester',
                'scholarship_ranking_runs.status',
                'scholarship_ranking_runs.calculated_at',
            ]);
    }
}
