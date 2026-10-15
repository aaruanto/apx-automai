<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mechanic;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The shop's mechanics, as records rather than login accounts.
 *
 * This replaces the old "Staff Accounts" screen, which created users with a
 * role dropdown that included admin — so anyone who reached Settings could
 * mint a full administrator. There are no accounts here at all now, so that
 * cannot happen.
 */
class MechanicController extends Controller
{
    public function index()
    {
        return view('admin.mechanics.index', [
            'mechanics' => Mechanic::withCount('bookings')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, SmsService $sms)
    {
        $data = $this->validated($request, $sms);

        Mechanic::create($data + ['status' => 'active']);

        return back()->with('success', 'Mechanic added.');
    }

    public function update(Request $request, SmsService $sms, int $id)
    {
        $mechanic = Mechanic::findOrFail($id);

        $mechanic->update($this->validated($request, $sms, $id));

        return back()->with('success', 'Mechanic updated.');
    }

    /**
     * Deactivate, never delete.
     *
     * A mechanic who has left still appears on the bookings they worked, and
     * removing the row would either break that history or silently blank it.
     * Deactivating keeps the record and stops them receiving new work, which
     * is what "removing someone" actually means here.
     */
    public function toggle(int $id)
    {
        $mechanic = Mechanic::findOrFail($id);

        $mechanic->update([
            'status' => $mechanic->isActive() ? 'inactive' : 'active',
        ]);

        return back()->with('success', $mechanic->isActive()
            ? $mechanic->name.' is active again and can be assigned work.'
            : $mechanic->name.' is now inactive and will not appear when assigning bookings.');
    }

    private function validated(Request $request, SmsService $sms, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                // Two mechanics with the same name make the assignment
                // dropdown useless, which is the only place this list is read.
                Rule::unique('mechanics', 'name')->ignore($ignoreId),
            ],
            'contact_number' => ['required', 'string', 'max:30'],
            'specialty'      => ['nullable', 'string', 'max:100'],
        ], [
            'name.unique' => 'There is already a mechanic by that name.',
        ]);

        // The same rules the SMS service uses, so the directory can never hold
        // a number that messaging would refuse.
        if ($sms->normalise($data['contact_number']) === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'contact_number' => 'Enter a Philippine mobile number, for example 09171234567.',
            ]);
        }

        return $data;
    }
}
