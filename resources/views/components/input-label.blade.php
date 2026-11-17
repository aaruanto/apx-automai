{{--
    The label used by the auth and profile pages.

    `required` renders the red asterisk that marks a field the backend will
    refuse empty. Pass it only where the FormRequest or $request->validate()
    rule actually contains `required` — the rule is the authority, and a label
    claiming otherwise is a bug in one direction or the other.

    The marker is aria-hidden and the asterisk is never the only signal: the
    input itself carries `required`, which is what a screen reader announces
    and what stops the browser submitting an empty field. Plain <label>
    elements elsewhere in the project get the same marker from the `.required`
    class in design-tokens.css, which cannot be aria-hidden because generated
    content is not an element — see the comment there.
--}}
@props(['value' => null, 'required' => false])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-700']) }}>
    {{ $value ?? $slot }}@if($required)<span class="required-mark" aria-hidden="true">&nbsp;*</span>@endif
</label>
