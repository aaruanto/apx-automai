<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bundle;
use App\Models\MaintenanceRule;
use App\Models\Service;
use App\Models\Vehicle;
use App\Services\MaintenanceDue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
    public function index(MaintenanceDue $due)
    {
        $rules = MaintenanceRule::with(['bundle', 'service'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.maintenance.index', [
            'rules'    => $rules,
            'bundles'  => Bundle::orderBy('name')->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
            'dueNow'   => $this->countDue($due, $rules->where('is_active', true)),
        ]);
    }

    /**
     * How many customers the next run would actually email.
     *
     * Worth showing, because the answer is not guessable from the rules alone.
     * A vehicle with no completed booking counts from the day it was
     * registered, so switching this feature on in a shop with a year of
     * existing records can mean a large batch of mail at the next run — and
     * the first anyone would know is when it lands.
     *
     * Deliberately the same calculation the command uses, including the
     * already-booked exclusion, rather than an approximation of it.
     */
    private function countDue(MaintenanceDue $due, $rules): int
    {
        if ($rules->isEmpty()) {
            return 0;
        }

        $count = 0;

        $vehicles = Vehicle::with('user')
            ->whereHas('user', fn ($q) => $q->where('is_active', true)->where('role', 'customer'));

        foreach ($vehicles->lazyById(200) as $vehicle) {
            if ($due->forVehicle($vehicle, $rules)->isNotEmpty() && ! $due->hasUpcomingBooking($vehicle)) {
                $count++;
            }
        }

        return $count;
    }

    public function store(Request $request)
    {
        $rule = MaintenanceRule::create($this->validated($request) + ['is_active' => true]);

        AuditLog::record('maintenance_rule.created', $rule, [], $rule->only([
            'name', 'interval_months', 'lead_days', 'vehicle_type',
        ]), label: $rule->name);

        return back()->with('success', 'Service interval added.');
    }

    public function update(Request $request, int $id)
    {
        $rule = MaintenanceRule::findOrFail($id);

        $before = $rule->only(['name', 'interval_months', 'lead_days', 'vehicle_type']);

        $rule->update($this->validated($request, $id));

        AuditLog::record('maintenance_rule.updated', $rule, $before, $rule->only([
            'name', 'interval_months', 'lead_days', 'vehicle_type',
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
            // Required now that time is the only interval: a rule without
            // one can never come due, and would sit in the list looking
            // configured while doing nothing.
            'interval_months' => ['required', 'integer', 'min:1', 'max:120'],
            'lead_days'       => ['required', 'integer', 'min:0', 'max:180'],
            'vehicle_type'    => ['nullable', 'in:car,motorcycle'],
            'sort_order'      => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'name.unique' => 'There is already an interval by that name.',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
