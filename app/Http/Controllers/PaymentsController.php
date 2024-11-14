<?php

namespace App\Http\Controllers;

use App\Models\Properties;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $properties = Properties::all(); // Get all properties
        return response()->json($properties); // Return properties as JSON
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:200',
            'rooms' => 'required|integer',
            'price' => 'required|integer',
            'type' => 'required|string|max:50',
            'status' => 'required|integer',
        ]);

        $property = Properties::create($request->all()); // Create a new property
        return response()->json($property, Response::HTTP_CREATED); // Return the created property
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $property = Properties::findOrFail($id); // Find property by ID
        return response()->json($property); // Return property as JSON
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:200',
            'rooms' => 'required|integer',
            'price' => 'required|integer',
            'type' => 'required|string|max:50',
            'status' => 'required|integer',
        ]);

        $property = Properties::findOrFail($id); // Find property by ID
        $property->update($request->all()); // Update the property
        return response()->json($property); // Return the updated property
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $property = Properties::findOrFail($id); // Find property by ID
        $property->delete(); // Delete the property
        return response()->json(null, Response::HTTP_NO_CONTENT); // Return no content status
    }
}
