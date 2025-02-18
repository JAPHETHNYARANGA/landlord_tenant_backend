<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Landlord;
use App\Models\Properties;
use App\Models\ServiceProvider;
use App\Models\Tenant;
use App\Rules\UniqueEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TenantController extends Controller
{
    public function index()
    {
        try {
            $tenants = Tenant::all(); // Retrieve all admins

            return response()->json([
                'tenants' => $tenants
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
            // Validate the incoming request
            $request->validate([
                'name' =>'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', new UniqueEmail([Tenant::class, Landlord::class, Admin::class, ServiceProvider::class])],
                'phone_number' =>'required|string|max:15',
                'property_id' =>'required|exists:properties,id',
                'room_type' =>'required|string',
                'houseNo' =>'required'
            ]);

            // Find the property and the specific room type
            $property = Properties::find($request->property_id);
            $roomType = $property->roomTypes()->where('type', $request->room_type)->first();

            if (!$roomType || $roomType->count <= 0) {
                // Log error if room type is unavailable
                Log::error('Room type not available', [
                    'property_id' => $request->property_id,
                    'room_type' => $request->room_type
                ]);
                return response()->json(['message' => 'No available units of this type'], 400);
            }

            // Create the tenant
            $tenant = Tenant::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'property_id' => $property->id,
                'house_no' => $request->houseNo // Assuming house_no represents the room assignment
            ]);

            // Reduce the count of the specific room type
            $roomType->count--;
            $roomType->save();

            // Generate a password creation token
            $token = Str::random(60);

            // Save token in a custom table
            DB::table('password_creates')->updateOrInsert(
                ['token' => $token],
                [
                    'email' => $tenant->email,
                    'user_type' => 'tenant'
                ]
            );

            // Send password creation link to the tenant's email
            $link = route('password.create', ['token' => $token]);
            Mail::send('password_set_link', ['link' => $link], function ($m) use ($tenant) {
                $m->from('info@landlordtenant.com', 'LandlordTenant');
                $m->to($tenant->email, $tenant->name)->subject('Set Password');
            });

            // Log success if tenant is created and email is sent
            Log::info('Tenant created and password link sent', [
                'tenant_id' => $tenant->id,
                'tenant_email' => $tenant->email
            ]);

            return response()->json([
                'message' => 'Tenant created successfully. A password creation link has been sent to their email.',
                'tenant' => $tenant
            ], 201);
        } catch (\Throwable $th) {
            // Log error if any exception occurs
            Log::error('Error occurred while creating tenant:', [
                'error_message' => $th->getMessage(),
                'error_trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }



    public function update(Request $request, Tenant $tenant)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'name' => 'nullable|required|string|max:255',
                'email' => ['nullable|required', 'string', 'email', 'max:255', new UniqueEmail([Tenant::class, Landlord::class, Admin::class, ServiceProvider::class], $tenant->id)],
                'phone_number' => 'nullable|required|string|max:15',
                // Include validation for image (optional)
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            // Check if there is an image in the request and handle the upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('public/tenant_images'); // Store in the 'tenant_images' directory

                // Update the tenant image path
                $tenant->image = $imagePath;
            }

            // Update other tenant details
            $tenant->update($request->except('image')); // Exclude the image field from being updated here

            return response()->json([
                'message' => 'Tenant updated successfully',
                'tenant' => $tenant
            ]);
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
            $tenant = Tenant::findOrFail($id); // Find the landlord by ID or fail if not found
            $tenant->delete(); // Delete the landlord

            return response()->json([
                'message' => 'Tenant deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    public function fetchLandlordTenants()
    {
        try {
            $landlord_id = Auth::user()->id;

            // Fetch the properties associated with the landlord
            $propertyIds = Properties::where('landlord_id', $landlord_id)->pluck('id');
            
            // Fetch tenants and eager load the associated properties
            $tenants = Tenant::whereIn('property_id', $propertyIds)
                ->with('property') // Use the correct relationship name here
                ->get();

            return response()->json([
                'tenants' => $tenants
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

}