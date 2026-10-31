{{-- Shows a flashed message through the shared alert modal.

     Settings and the bundle list each grew their own copy of this block, and
     every other admin page that flashes has none — so BookingController's
     "Booking #… created successfully", CustomerController's read-only notice
     and several more are set on the session and never reach a screen. A
     redirect carrying a message nobody sees is a silent redirect.

     Include it inside a @push('scripts') block, after the alert modal
     component is on the page.

     $keys maps a session key to the modal variant it should use. --}}
@props(['keys' => ['success' => 'success', 'error' => 'error', 'warning' => 'info']])

@foreach ($keys as $key => $variant)
    @if (session($key))
        ApxAlertModal.show({
            variant: @json($variant),
            title:   @json(['success' => 'Done', 'error' => 'Cannot do that', 'warning' => 'Take a look'][$key] ?? 'Notice'),
            message: @json(session($key)),
        });
    @endif
@endforeach
