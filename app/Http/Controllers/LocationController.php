<?php

namespace App\Http\Controllers;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function addLocation(Request $request) {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);
        $location = Location::updateOrCreate(
            ['user_id' => auth('sanctum')->id()],
            $validated
        );
        return response()->json([
            'message' => 'Location added successfully',
            'location' => $location,
        ], 201);
    }
}
