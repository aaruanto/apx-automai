<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bundle;
use App\Models\MaintenanceRule;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The service intervals that decide when a vehicle is due.
 *
 * These drive the service-due reminders, so the shop — not the developer —
 * decides what "due" means. Three sensible rules ship seeded; this screen is
 * how they get adjusted once the shop has an opinion.
 *
 * Every change is written to the audit log. An interval change alters who gets
 * emailed and when, which is exactly the kind of edit someone will later want
 * to account for.
 */
class MaintenanceRuleController extends Controller
{
    public function index()
    {
        return view('admin.maintenance.index', [
            'rules'    => MaintenanceRule::with(['bundle', 'service'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'bundles'  => Bundle::orderBy('name')->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $rule = MaintenanceRule::create($this->validated($request) + ['is_active' => true]);

        AuditLog::record('maintenance_rule.created', $rule, [], $rule->only([
            'name', 'interval_months', 'interval_km', 'lead_days', 'vehicle_type',
        ]), label: $rule->name);

        return back()->with('success', 'Service interval added.');
    }

    public function update(Request $request, int $id)
    {
        $rule = MaintenanceRule::findOrFail($id);

        $before = $rule->only(['name', 'interval_months', 'interval_km', 'lead_days', 'vehicle_type']);

        $rule->update($this->validated($request, $id));

        AuditLog::record('maintenance_rule.updated', $rule, $before, $rule->only([
            'name', 'interval_months', 'interval_km', 'lead_days', 'vehicle_type',
        ]), label: $rule->name);

        return back()->with('success', 'Service interval updated.');
    }

    /**
     * Switch a rule off rather than delete it.
     *
     * Reminders already sent reference the rule that raised them, so deleting
     * the row would either break that history or blank it. An inactive rule
     * stops producing new reminders, which is what switching one off means.
     */
    public function toggle(int $id)
    {
        $rule = MaintenanceRule::findOrFail($id);

        $rule->update(['is_active' => ! $rule->is_active]);

        AuditLog::record(
            'maintenance_rule.toggled', $rule,
            ['is_active' => ! $rule->is_active], ['is_active' => $rule->is_active],
            label: $rule->name,
        );

        return back()->with('success', $rule->is_active
            ? $rule->name.' is active again — customers will be reminded when this falls due.'
            : $rule->name.' is switched off and will raise no further reminders.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                // The name is what the customer reads in the reminder, and
                // what the dashboard badge shows, so duplicates are confusing
                // rather than merely untidy.
                Rule::unique('maintenance_rules', 'name')->ignore($ignoreId),
            ],
            'description'     => ['nullable', 'string', 'max:255'],
            'bundle_id'       => ['nullable', 'integer', 'exists:bundles,id'],
            'service_id'      => ['nullable', 'integer', 'exists:services,id'],
            'interval_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'interval_km'     => ['nullable', 'integer', 'min:100', 'max:200000'],
            'lead_days'       => ['required', 'integer', 'min:0', 'max:180'],
            'vehicle_type'    => ['nullable', 'in:car,motorcycle'],
            'sort_order'      => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'name.unique' => 'There is already an interval by that name.',
        ]);

        // A rule with neither interval can never come due, so it would sit in
        // the list looking configured and do nothing at all.
        if (empty($data['interval_months']) && empty($data['interval_km'])) {
            throw ValidationException::withMessages([
                'interval_months' => 'Give a time interval, a distance interval, or both — a rule with neither can never fall due.',
            ]);
        }

        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
