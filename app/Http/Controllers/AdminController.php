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

class AdminController extends Controller
{

    public function index()
    {
        try {
            $admins = Admin::all(); // Retrieve all admins

            return response()->json([
                'admins' => $admins
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
            $admin = Admin::create([
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
                    'email' => $admin->email,
                    'user_type' => 'admin' 
                ] 
                
            );

            // Create the link to set the password
            $link = route('password.create', ['token' => $token]);

            // Send password creation link to the admin's email
            Mail::send('password_set_link', ['link' => $link], function ($m) use ($admin) {
                $m->from('info@landlordtenant.com', 'LandlordTenant');
                $m->to($admin->email, $admin->name)->subject('Set Password');
            });

            return response()->json([
                'message' => 'Admin created successfully. A password creation link has been sent to their email.',
                'admin' => $admin
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
  


    public function update(Request $request, Admin $admin)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', new UniqueEmail([Tenant::class, Landlord::class, Admin::class, ServiceProvider::class], $admin->id)],
                'password' => 'nullable|string|min:8',
                'phone_number' => 'required|string|max:15',
                'address' => 'required|string',
            ]);

            $admin->update([
                'name' => $request->name,
                'email' => $request->email,
                'address'=>$request->address,
                'phone_number'=>$request->phone_number,
                'password' => $request->password ? bcrypt($request->password) : $admin->password,
            ]);

            return response()->json([
                'message' => 'Admin updated successfully',
                'admin' => $admin
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
            $admin = Admin::findOrFail($id); // Find the landlord by ID or fail if not found
            $admin->delete(); // Delete the landlord

            return response()->json([
                'message' => 'Admin deleted successfully'
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}