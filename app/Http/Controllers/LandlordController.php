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
                'phone_number' => 'required|string|max:15',
                'address' => 'required|string',
            ]);

            // Create admin without a password
            $landlord  = Landlord::create([
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
            Mail::send('password_set_link', ['link' => $link], function ($m) use ($landlord ) {
                $m->from('info@landlordtenant.com', 'LandlordTenant');
                $m->to($landlord ->email, $landlord ->name)->subject('Set Password');
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

    public function update(Request $request, Landlord $landlord)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', new UniqueEmail([Tenant::class, Landlord::class, Admin::class, ServiceProvider::class], $landlord->id)] ,
                'phone_number' => 'required|string|max:15',
                'address' => 'required|string',
            ]);

            $landlord->update($request->all());

            return response()->json([
                'message' => 'Landlord updated successfully',
                'landlord' => $landlord
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

}