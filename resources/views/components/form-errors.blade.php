{{-- Validation messages for a full-page admin form.

     A form that fails server-side validation and redirects back renders again
     with no sign of what was wrong, so the only way to find out is to guess.
     The booking form grew this banner for that reason; the customer form had
     no error display at all, which made refusing a plate already registered to
     another account look exactly like the page reloading for no reason.

     The modal-based admin pages (mechanics, maintenance rules, bundles)
     deliberately do not use this: they reopen their modal and put each message
     against its own field, which is better where the markup allows it.

     $title says which form could not be saved, since the banner sits at the top
     of a page that may hold more than one card. --}}
@props(['title' => 'This form could not be saved.'])

@if ($errors->any())
    <div class="alert alert-danger" role="alert"
         style="background:var(--red-glow);border:1px solid var(--red);color:var(--text);
                border-radius:8px;padding:12px 16px;margin-bottom:18px;font-size:.86rem;line-height:1.6;">
        <strong style="display:block;margin-bottom:4px;">{{ $title }}</strong>
        <ul style="margin:0;padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
