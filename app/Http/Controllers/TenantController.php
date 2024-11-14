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
            $request->validate([
                'name' =>'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', new UniqueEmail([Tenant::class, Landlord::class, Admin::class, ServiceProvider::class])],
                'phone_number' =>'required|string|max:15',
                'address' =>'required|string',
                'property_id' =>'required|exists:properties,id',
                'room_type' =>'required|string',
                'houseNo' =>'required'
            ]);

            // Find the property and the specific room type
            $property = Properties::find($request->property_id);
            $roomType = $property->roomTypes()->where('type', $request->room_type)->first();


            if (!$roomType || $roomType->count <= 0) {
                return response()->json(['message' => 'No available units of this type'], 400);
            }

            // Create the tenant
            $tenant = Tenant::create([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
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

            // Create the link to set the password
            $link = route('password.create', ['token' => $token]);

            // Send password creation link to the tenant's email
            Mail::send('password_set_link', ['link' => $link], function ($m) use ($tenant) {
                $m->from('info@landlordtenant.com', 'LandlordTenant');
                $m->to($tenant->email, $tenant->name)->subject('Set Password');
            });

            return response()->json([
            'message' => 'Tenant created successfully. A password creation link has been sent to their email.',
                'tenant' => $tenant
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
            'status' => false,
            'message' => $th->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, Tenant $tenant)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', new UniqueEmail([Tenant::class, Landlord::class, Admin::class, ServiceProvider::class], $tenant->id)],
                'phone_number' => 'required|string|max:15',
                'address' => 'required|string',
            ]);

            $tenant->update($request->all());

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