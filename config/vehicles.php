<?php

/*
|--------------------------------------------------------------------------
| Vehicles the shop sees
|--------------------------------------------------------------------------
|
| Manufacturer => the models of theirs that turn up in Tandang Sora, split by
| whether it is a car or a motorcycle. It drives the make and model inputs on
| every form that records a vehicle.
|
| It lived twice, verbatim, in index.blade.php — once for the service-check
| widget and once for the booking modal — and nowhere else, so the customer
| dashboard and both admin forms could not use it and each invented their own
| idea of what a make and a model were. That is the whole reason this file
| exists: one list, so every form that writes to vehicles.make and
| vehicles.model writes the same shape.
|
| This is a convenience, not a constraint. The forms suggest from it and still
| accept anything typed, because a customer arriving in a Mazda should not be
| turned away by a list that has never heard of one. Admin is free text for
| the same reason, only more so.
|
*/

return [

    'car' => [
        'Toyota'     => ['Vios', 'Innova', 'Fortuner', 'Hiace', 'Camry', 'Corolla Cross', 'Rush'],
        'Honda'      => ['City', 'Civic', 'BR-V', 'CR-V', 'HR-V', 'Jazz', 'Brio'],
        'Mitsubishi' => ['Mirage', 'Mirage G4', 'Montero Sport', 'Xpander', 'L300', 'Strada'],
        'Suzuki'     => ['Alto', 'Celerio', 'Ertiga', 'Jimny', 'Swift', 'Dzire'],
        'Ford'       => ['EcoSport', 'Everest', 'Explorer', 'Ranger', 'Territory'],
        'Hyundai'    => ['Accent', 'Tucson', 'Santa Fe', 'Reina', 'Stargazer'],
        'Nissan'     => ['Almera', 'Navara', 'Terra', 'X-Trail'],
        'Kia'        => ['Soluto', 'Stonic', 'Sportage', 'Carnival'],
    ],

    'motorcycle' => [
        'Honda'    => ['Click 125i', 'Click 150i', 'BeAT', 'PCX 160', 'ADV 160', 'CBR150R', 'XRM 125'],
        'Yamaha'   => ['Mio i 125', 'Mio Gear', 'NMAX', 'Aerox', 'Sniper 150', 'FZ-S'],
        'Kawasaki' => ['Barako II', 'CT100B', 'Rouser NS 200', 'Ninja 400'],
        'Suzuki'   => ['Skydrive 125', 'Raider R150', 'GSX-R150'],
        'TVS'      => ['Apache RTR 160', 'Ntorq 125', 'King Duramax'],
        'Kymco'    => ['Agility 125', 'Like 150i'],
    ],

];
