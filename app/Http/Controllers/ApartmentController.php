<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ApartmentImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ApartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Eager load images for each apartment
            $apartments = Apartment::with('images')->orderBy('created_at', 'desc')->get();
            return response()->json($apartments);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'bedrooms' => 'required|integer',
                'bathrooms' => 'required|integer',
                'price' => 'required|numeric',
                'description' => 'nullable|string',
                'images' => 'required|array',
                'images.*' => 'required|image', // Ensure it's an image
            ]);

            // Create the apartment
            $apartment = Apartment::create($request->except('images'));

            // Save images if provided
            if ($request->has('images')) {
                foreach ($request->file('images') as $image) {
                    // Store the image and get its path
                    $imagePath = $image->store('apartments', 'public');

                    // Create the full URL for the stored image
                    $imageUrl = URL::to(Storage::url($imagePath)); // Create full URL

                    $imageUrl = str_replace('http://127.0.0.1:8000', 'https://9390-102-68-76-239.ngrok-free.app', $imageUrl);

                    ApartmentImage::create([
                        'apartment_id' => $apartment->id,
                        'image_url' => $imageUrl, // Store the full URL
                    ]);
                }
            }

            // Load images with URLs for the response
            return response()->json($apartment->load('images'), 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            // Fetch apartment with images
            $apartment = Apartment::with('images')->findOrFail($id);
            return response()->json($apartment);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try{

            $apartment = Apartment::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|max:255',
            'bedrooms' => 'sometimes|required|integer',
            'bathrooms' => 'sometimes|required|integer',
            'price' => 'sometimes|required|numeric',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string',
        ]);

        $apartment->update($request->all());

        return response()->json($apartment);

        }catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $apartment = Apartment::findOrFail($id);
            $apartment->delete();
    
            return response()->json("apprtment deleted successfully", 200);

        }catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
       
    }
}
