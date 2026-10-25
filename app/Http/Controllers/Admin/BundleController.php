<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Bundle;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BundleController extends Controller
{
    public function index()
    {
        return view('admin.bundles.index', [
            'bundles'  => Bundle::with('services')->withCount('bookings')->orderBy('name')->get(),
            'services' => Service::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $bundle = Bundle::create($data);
        $bundle->services()->sync($data['service_ids']);

        AuditLog::record(
            'bundle.created', $bundle, [],
            ['price' => (float) $bundle->price, 'services' => $data['service_ids']],
            label: $bundle->name,
        );

        return back()->with('success', 'Bundle created.')
            ->with('warning', $this->vehicleTypeClash($bundle));
    }

    public function update(Request $request, int $id)
    {
        $bundle   = Bundle::with('services')->findOrFail($id);
        $data     = $this->validated($request, $id);
        $oldPrice = (float) $bundle->price;

        $bundle->update($data);
        $bundle->services()->sync($data['service_ids']);

        // Only the price is logged, same rule as the service price list: a
        // renamed bundle changes nothing about what anything earned, and past
        // bookings keep their own snapshot regardless.
        if ($oldPrice !== (float) $bundle->price) {
            AuditLog::record(
                'bundle.price_changed', $bundle,
                ['price' => $oldPrice], ['price' => (float) $bundle->price],
                label: $bundle->name,
            );
        }

        return back()->with('success', 'Bundle updated.')
            ->with('warning', $this->vehicleTypeClash($bundle->fresh('services')));
    }

    /**
     * Switch a bundle off rather than delete it.
     *
     * Bookings reference the bundle they were sold under; removing the row
     * would leave those bookings unable to say what the customer bought.
     */
    public function toggle(int $id)
    {
        $bundle = Bundle::findOrFail($id);
        $bundle->update(['is_active' => ! $bundle->is_active]);

        return back()->with('success', $bundle->is_active
            ? $bundle->name.' is live and bookable again.'
            : $bundle->name.' is switched off and will not appear to customers.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255', Rule::unique('bundles', 'name')->ignore($ignoreId)],
            'description'   => ['nullable', 'string', 'max:1000'],
            'price'         => ['required', 'numeric', 'min:0'],
            // Two services minimum: one service at a discount is a price
            // change, not a bundle, and belongs in the price list.
            'service_ids'   => ['required', 'array', 'min:2'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'starts_at'     => ['nullable', 'date'],
            'ends_at'       => ['nullable', 'date', 'after_or_equal:starts_at'],
            'vehicle_type'  => ['nullable', Rule::in(['car', 'motorcycle'])],
        ], [
            'service_ids.min'       => 'A bundle needs at least two services.',
            'service_ids.required'  => 'Choose the services this bundle includes.',
            'ends_at.after_or_equal' => 'The end date cannot be before the start date.',
        ]);

        $data['service_ids'] = array_values(array_unique(array_map('intval', $data['service_ids'])));

        // A bundle priced at or above its parts is not a bundle. Caught here
        // rather than left to the customer to notice.
        $listPrice = (float) Service::whereIn('id', $data['service_ids'])->sum('price');

        if ((float) $data['price'] >= $listPrice) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'price' => 'The bundle price must be below the ₱'.number_format($listPrice, 2)
                           .' these services cost separately, or it saves the customer nothing.',
            ]);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    /**
     * A bundle narrower than something it contains.
     *
     * Both bundles and services carry a vehicle_type, and nothing stops them
     * disagreeing: a motorcycle bundle can be given a car-only service, and
     * then no vehicle on earth can book it — the bundle gate lets a bike
     * through and the service gate turns it away.
     *
     * Surfaced rather than blocked. The shop may be mid-edit, or may know
     * something the catalogue does not, and refusing the save would leave
     * them nowhere. Null returns nothing, so no banner appears.
     */
    private function vehicleTypeClash(Bundle $bundle): ?string
    {
        if (! $bundle->vehicle_type) {
            return null;
        }

        $clashing = $bundle->services->reject(
            fn (Service $service) => $service->suitsVehicleType($bundle->vehicle_type)
        );

        if ($clashing->isEmpty()) {
            return null;
        }

        return 'This bundle is for '.$bundle->vehicle_type.'s, but '
            .$clashing->pluck('name')->join(', ', ' and ')
            .($clashing->count() === 1 ? ' is' : ' are')
            .' not. Nobody will be able to book it until they agree.';
    }
}
