<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Landlord;
use App\Models\ServiceProvider;
use App\Models\Tenant;
use App\Rules\UniqueEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ServiceProviderController extends Controller
{
    public function index()
    {
        try {
            $serviceProvider = ServiceProvider::all(); // Retrieve all admins

            return response()->json([
                'provider' => $serviceProvider
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
                'phone_number' => 'required|string|max:15',
                'address' => 'required|string',
                'designation' => 'required'
            ]);

            // Create admin without a password
            $serviceProvider  = ServiceProvider::create([
                'name' => $request->name,
                'email' => $request->email,
                'address' => $request->address,
                'phone_number' => $request->phone_number,
                'designation' => $request->designation
            ]);

            // Generate a password creation token
            $token = Str::random(60);

            // Save token in a custom table
            DB::table('password_creates')->updateOrInsert(
                ['token' => $token],
                [
                    'email' => $serviceProvider->email,
                    'user_type' => 'serviceProvider'
                ]
            );

            // Create the link to set the password
            $link = route('password.create', ['token' => $token]);

            // Send password creation link to the admin's email
            Mail::send('password_set_link', ['link' => $link], function ($m) use ($serviceProvider) {
                $m->from('info@landlordtenant.com', 'LandlordTenant');
                $m->to($serviceProvider->email, $serviceProvider->name)->subject('Set Password');
            });

            return response()->json([
                'message' => 'service Provider created successfully. A password creation link has been sent to their email.',
                'provider' => $serviceProvider
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, ServiceProvider $serviceProvider)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', new UniqueEmail([Tenant::class, Landlord::class, Admin::class, ServiceProvider::class], $serviceProvider->id)],
                'phone_number' => 'required|string|max:15',
                'address' => 'required|string',
            ]);

            $serviceProvider->update($request->all());

            return response()->json([
                'message' => 'service Provider updated successfully',
                'provider' => $serviceProvider
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
            // Find the service provider by ID or fail if not found
            $serviceProvider = ServiceProvider::findOrFail($id);

            // Delete the service provider's auth tokens (Sanctum or Passport)
            // For Laravel Sanctum (you can adjust based on the token storage you're using)
            $serviceProvider->tokens->each(function ($token) {
                $token->delete();
            });

            // Delete any password reset tokens for this provider
            DB::table('password_resets')->where('email', $serviceProvider->email)->delete();

            // Delete any password creation tokens for this provider
            DB::table('password_creates')->where('email', $serviceProvider->email)->delete();

            // Now, delete the service provider record itself
            $serviceProvider->delete();

            return response()->json([
                'message' => 'Service provider deleted successfully and all associated tokens removed.',
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
