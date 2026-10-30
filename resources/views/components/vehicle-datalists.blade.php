{{-- Suggestions for a make and a model input, from config/vehicles.php.

     Datalists rather than selects on purpose. The booking modal on the
     landing page uses cascading dropdowns, which guarantees clean data but
     can only offer what the list already knows — a customer arriving in a
     Mazda has nowhere to go. These forms suggest the usual answers and still
     accept anything typed, which is what admin needs, since admin is the path
     that has to be able to record whatever actually turned up.

     Every make and every model is offered, both vehicle types together. A
     narrower list would need the type chosen first, and getting that wrong
     hides the right answer rather than merely failing to suggest it. --}}
@props(['makes', 'models'])

@php
    // config('vehicles') is keyed by vehicle type first, so the manufacturers
    // are one level down and the models two.
    $catalogue = config('vehicles', []);
    $allMakes  = collect($catalogue)->flatMap(fn ($byMake) => array_keys($byMake))->unique()->sort()->values();
    $allModels = collect($catalogue)->flatMap(fn ($byMake) => collect($byMake)->flatten())->unique()->sort()->values();
@endphp

<datalist id="{{ $makes }}">
    @foreach ($allMakes as $make)
        <option value="{{ $make }}"></option>
    @endforeach
</datalist>

<datalist id="{{ $models }}">
    @foreach ($allModels as $model)
        <option value="{{ $model }}"></option>
    @endforeach
</datalist>
