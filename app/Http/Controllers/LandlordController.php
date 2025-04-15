<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Landlord;
use App\Models\ServiceProvider;
use App\Models\Tenant;
use App\Rules\UniqueEmail;
use App\Rules\UniquePhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;


class LandlordController extends Controller
{

    public function index()
    {
        try {
            $landlords = Landlord::all(); // Retrieve all admins

            return response()->json([
                'landlords' => $landlords
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', new UniqueEmail([Tenant::class, Landlord::class, Admin::class, ServiceProvider::class])],
                'phone_number' => ['required', new UniquePhoneNumber],
                'address' => 'required|string',
            ]);

            $userId = Str::random(15);

            // Create landlord
            $landlord = Landlord::create([
                'user_id' => $userId,
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'phone_number' => $request->phone_number,
            ]);

            // Generate a password creation token
            $token = Str::random(60);

            // Save token in a custom table
            DB::table('password_creates')->updateOrInsert(
                ['token' => $token],
                [
                    'email' => $landlord->email,
                    'user_type' => 'landlord'
                ]
            );

            // Create the link to set the password
            $link = route('password.create', ['token' => $token]);

            // Send password creation link to the admin's email
            Mail::send('password_set_link', ['link' => $link], function ($m) use ($landlord) {
                $m->from('info@landlordtenant.com', 'LandlordTenant');
                $m->to($landlord->email, $landlord->name)->subject('Set Password');
            });

            return response()->json([
                'message' => 'Landlord created successfully. A password creation link has been sent to their email.',
                'landlord' => $landlord
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            // Validate the incoming request to ensure only an image is uploaded
            $request->validate([
                'image' => 'nullable|image', // Image is optional, but if provided, it should be an image
            ]);

            // Fetch the authenticated user
            $auth = Auth::user()->id;
            $landlord = Landlord::where('id', $auth)->first();



            // Check if an image is being uploaded
            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                if ($landlord->image) {
                    // Remove the 'storage/' part from the image URL before deleting
                    $imagePath = str_replace(url('storage') . '/', '', $landlord->image);
                    Storage::delete('public/landlord_images/' . $imagePath); // Delete the old image
                }

                // Store the new image and get the path
                $imagePath = $request->file('image')->store('public/landlord_images'); // Store in 'landlord_images' directory

                // Get the public URL for the stored image
                $imageUrl = asset('storage/' . str_replace('public/', '', $imagePath)); // Remove 'public/' from the path

                // Update the landlord image path with the new URL
                $landlord->image = $imageUrl;
            }

            // Explicitly update the image field
            $landlord->update([
                'image' => $landlord->image, // Only update the image column
            ]);

            return response()->json([
                'message' => 'Landlord image updated successfully',
                'landlord' => $landlord
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }




    public function destroy($id)
    {
        try {
            $landlord = Landlord::findOrFail($id); // Find the landlord by ID or fail if not found
            $landlord->delete(); // Delete the landlord

            return response()->json([
                'message' => 'Landlord deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $landlord = Landlord::findOrFail($id); // Find landlord by ID or fail

            return response()->json([
                'landlord' => $landlord
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
