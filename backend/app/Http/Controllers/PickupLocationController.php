<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePickupLocationRequest;
use App\Http\Requests\UpdatePickupLocationRequest;
use App\Models\PickupLocation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PickupLocationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('PickupLocations/Index', [
            'locations' => PickupLocation::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function store(StorePickupLocationRequest $request): RedirectResponse
    {
        PickupLocation::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return back()->with('success', 'Pickup location created.');
    }

    public function update(UpdatePickupLocationRequest $request, PickupLocation $pickupLocation): RedirectResponse
    {
        $pickupLocation->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return back()->with('success', 'Pickup location updated.');
    }

    public function destroy(PickupLocation $pickupLocation): RedirectResponse
    {
        $pickupLocation->update(['is_active' => false]);

        return back()->with('success', 'Pickup location deactivated.');
    }
}
