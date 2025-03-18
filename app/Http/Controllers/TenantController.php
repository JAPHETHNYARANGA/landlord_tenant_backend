<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Landlord;
use App\Models\Properties;
use App\Models\ServiceProvider;
use App\Models\Tenant;
use App\Rules\UniqueEmail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
                'name' => 'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', new UniqueEmail([Tenant::class, Landlord::class, Admin::class, ServiceProvider::class])],
                'phone_number' => 'required|string|max:15',
                'property_id' => 'required|exists:properties,id',
                'room_type' => 'required|string',
                'houseNo' => 'required'
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

            $userId = Str::random(15);

            // Create the tenant
            $tenant = Tenant::create([
                'user_id' => $userId,
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



    public function update(Request $request)
    {
        try {
            // Validate the incoming request to ensure only an image is uploaded
            $request->validate([
                'image' => 'nullable|image', // Image is optional, but if provided, it should be an image
            ]);

            // Fetch the authenticated user
            $auth = Auth::user()->id;
            $tenant = Tenant::where('id', $auth)->first();



            // Check if an image is being uploaded
            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                if ($tenant->image) {
                    // Remove the 'storage/' part from the image URL before deleting
                    $imagePath = str_replace(url('storage') . '/', '', $tenant->image);
                    Storage::delete('public/tenant_images/' . $imagePath); // Delete the old image
                }

                // Store the new image and get the path
                $imagePath = $request->file('image')->store('public/tenant_images'); // Store in 'landlord_images' directory

                // Get the public URL for the stored image
                $imageUrl = asset('storage/' . str_replace('public/', '', $imagePath)); // Remove 'public/' from the path

                // Update the landlord image path with the new URL
                $tenant->image = $imageUrl;
            }

            // Explicitly update the image field
            $tenant->update([
                'image' => $tenant->image, // Only update the image column
            ]);

            return response()->json([
                'message' => 'Landlord image updated successfully',
                'landlord' => $tenant
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

            // Fetch tenants and eager load the associated properties and rent payments
            $tenants = Tenant::whereIn('property_id', $propertyIds)
                ->with('property', 'rentPayments')  // Eager load rentPayments relationship
                ->get()
                ->map(function ($tenant) {
                    // Check if the tenant has paid rent up to today's date
                    $lastPayment = $tenant->rentPayments()->where('payment_date', '<=', now())->orderBy('payment_date', 'desc')->first();

                    // Determine rent status
                    if ($lastPayment) {
                        // Convert the payment_date string to a Carbon object and then format it
                        $paymentDate = Carbon::parse($lastPayment->payment_date);
                        $tenant->paymentStatus = 'Paid up to ' . $paymentDate->format('Y-m-d');
                    } else {
                        $tenant->paymentStatus = 'Arrears';
                    }

                    return $tenant;
                });

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
