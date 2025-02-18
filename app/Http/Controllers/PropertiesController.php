<?php

namespace App\Http\Controllers;

use App\Models\Properties;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PropertiesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Retrieve all properties with their related room types
            $properties = Properties::with('roomTypes')
            ->orderBy('created_at', 'desc')
            ->get(); 
            return response()->json($properties);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        } 
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Typically, this method is used for showing a form in web applications.
        // For API, this might be omitted or replaced with a different approach.
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required',
                'location' => 'required',
                'rooms' => 'required|integer',
                'type' => 'required|string',
                'status' => 'required|string',
                'landlord_id' => 'required|exists:landlords,id', // Validate that landlord_id exists in the landlords table
                'room_types' => 'required|array', // Expecting an array of room types
                'guard_phone' =>'nullable'
            ]);

            // Create property with landlord_id from the request
            $property = Properties::create($request->except('room_types'));

            // Create room types
            foreach ($request->room_types as $roomType) {
                RoomType::create([
                    'property_id' => $property->id,
                    'type' => $roomType['type'],
                    'count' => $roomType['count'],
                    'price' => $roomType['price'],
                ]);
            }

            return response()->json($property, 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $property = Properties::find($id); // Find property by id

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        return response()->json($property); // Return the property

        }catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        } 
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Typically, this method is used for showing a form in web applications.
        // For API, this might be omitted or replaced with a different approach.
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try{
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'location' => 'sometimes|string|max:255',
                'rooms' => 'sometimes|integer',
                'price' => 'sometimes|numeric',
                'type' => 'sometimes|string|max:50',
                'status' => 'sometimes|string|max:50',
                'guard_phone' =>'nullable'
            ]);
    
            $property = Properties::find($id); // Find property by id
    
            if (!$property) {
                return response()->json(['message' => 'Property not found'], 404);
            }
    
            $property->update($request->all()); // Update the property
            return response()->json($property); // Return the updated property
        }catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        } 
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $property = Properties::find($id); // Find property by id

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        $property->delete(); // Delete the property
        return response()->json(['message' => 'Property deleted successfully']); // Return success message

        }catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        } 
        
    }

    public function fetchLandlordProperties(){
        try{
            $landlord = Auth::user()->id;

            $properties = Properties::where('landlord_id', $landlord)->get(); // Retrieve all properties
            return response()->json($properties);

        }catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        } 
    }

    public function fetchRoomTypes($propertyId)
    {
        try {
            $property = Properties::find($propertyId);
            if (!$property) {
                return response()->json(['message' => 'Property not found'], 404);
            }

            $roomTypes = RoomType::where('property_id', $propertyId)->get();
            return response()->json($roomTypes);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

}