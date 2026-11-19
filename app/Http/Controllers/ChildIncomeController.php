<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardianIncomeRequest;
use App\Models\Guardian;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Orang tua/wali mengisi pendapatannya sendiri. Hanya profil orang tua yang
 * terhubung dengan akun yang sedang login yang dapat diubah.
 */
class ChildIncomeController extends Controller
{
    public function edit(Request $request): View
    {
        $guardian = $this->guardian($request);

        return view('children.income', [
            'guardian' => $guardian,
            'children' => $guardian === null
                ? collect()
                : $guardian->students()->orderBy('full_name')->get(),
        ]);
    }

    public function update(GuardianIncomeRequest $request): RedirectResponse
    {
        $guardian = $this->guardian($request);

        abort_if($guardian === null, 403, 'Akun ini belum terhubung dengan data orang tua/wali.');

        $guardian->update($request->validated());

        return back()->with('success', 'Pendapatan berhasil disimpan.');
    }

    private function guardian(Request $request): ?Guardian
    {
        return Guardian::query()->where('user_id', $request->user()->id)->first();
    }
}
