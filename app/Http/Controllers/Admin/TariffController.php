<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tariff;
use App\Models\WaterSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TariffController extends Controller
{
    public function index(): View
    {
        return view('admin.tariffs.index', [
            'tariffs' => Tariff::query()
                ->with('tiers')
                ->orderByDesc('effective_from')
                ->orderBy('usage_type')
                ->paginate(10),
            'currency' => WaterSetting::current()->currency,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'usage_type' => ['required', 'in:residential,business'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'fixed_fee' => ['required', 'integer', 'min:0'],
            'tax_percent' => ['required', 'integer', 'between:0,100'],
            'tiers' => ['required', 'array', 'min:1', 'max:10'],
            'tiers.*' => ['required', 'array:consumption_from,consumption_to,unit_price'],
            'tiers.*.consumption_from' => ['required', 'integer', 'min:1'],
            'tiers.*.consumption_to' => ['present', 'nullable', 'integer', 'min:1'],
            'tiers.*.unit_price' => ['required', 'integer', 'min:0'],
        ]);

        $validator->after(function ($validator): void {
            $data = $validator->getData();

            if (
                $validator->errors()->isNotEmpty()
                || ! isset($data['tiers'], $data['usage_type'], $data['effective_from'])
                || ! is_array($data['tiers'])
            ) {
                return;
            }

            $tiers = array_values($data['tiers']);
            $expectedStart = 1;

            foreach ($tiers as $index => $tier) {
                if ((int) $tier['consumption_from'] !== $expectedStart) {
                    $validator->errors()->add(
                        "tiers.$index.consumption_from",
                        'Les tranches doivent commencer à 1 et se suivre sans trou ni chevauchement.',
                    );
                }

                $end = ($tier['consumption_to'] ?? null) === null || ($tier['consumption_to'] ?? '') === ''
                    ? null
                    : (int) $tier['consumption_to'];

                if ($end !== null && $end < (int) $tier['consumption_from']) {
                    $validator->errors()->add("tiers.$index.consumption_to", 'La fin doit être supérieure ou égale au début.');
                }

                if ($end === null && $index !== array_key_last($tiers)) {
                    $validator->errors()->add("tiers.$index.consumption_to", 'Seule la dernière tranche peut être sans limite.');
                }

                if ($end !== null) {
                    $expectedStart = $end + 1;
                }
            }

            $lastTierEnd = $tiers[array_key_last($tiers)]['consumption_to'] ?? null;

            if ($lastTierEnd !== null && $lastTierEnd !== '') {
                $validator->errors()->add(
                    'tiers.'.array_key_last($tiers).'.consumption_to',
                    'La dernière tranche doit couvrir toute consommation restante.',
                );
            }

            $effectiveFrom = $data['effective_from'];
            $effectiveUntil = $data['effective_until'] ?? null;
            $overlappingTariffExists = Tariff::query()
                ->where('usage_type', $data['usage_type'])
                ->when($effectiveUntil !== null, fn ($query) => $query->whereDate('effective_from', '<=', $effectiveUntil))
                ->where(fn ($query) => $query
                    ->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $effectiveFrom))
                ->exists();

            if ($overlappingTariffExists) {
                $validator->errors()->add('effective_from', 'Les dates chevauchent déjà un tarif de cette catégorie.');
            }
        });

        $validated = $validator->validate();
        DB::transaction(function () use ($validated): void {
            $tariff = Tariff::query()->create([
                'name' => $validated['name'],
                'usage_type' => $validated['usage_type'],
                'effective_from' => $validated['effective_from'],
                'effective_until' => $validated['effective_until'] ?? null,
                'fixed_fee' => $validated['fixed_fee'],
                'tax_percent' => $validated['tax_percent'],
            ]);

            $tariff->tiers()->createMany(
                collect($validated['tiers'])
                    ->values()
                    ->map(fn (array $tier, int $position): array => [
                        ...$tier,
                        'position' => $position + 1,
                        'consumption_to' => $tier['consumption_to'] ?: null,
                    ])
                    ->all(),
            );

        });

        return redirect()->route('admin.tariffs.index')->with('status', 'tariff-created');
    }
}
