<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\MoveOutRequest;
use App\Models\Notifications;
use App\Models\SuperAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MoveOutRequestController extends Controller
{
     /**
     * Display a listing of move-out requests for the authenticated tenant.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get the authenticated tenant
        $tenant = Auth::user();

        // Fetch all move-out requests for the tenant
        $moveOutRequests = $tenant->moveOutRequests;

        // Return the move-out requests as a JSON response
        return response()->json($moveOutRequests, 200);
    }

    /**
     * Store a newly created move-out request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */


     public function store(Request $request)
     {
         try {
             // Validate incoming request data
             $request->validate([
                 'move_out_date' => 'required|date',
                 'move_out_reason' => 'required|string|max:255',
             ]);
     
             // Get the authenticated tenant
             $tenant = Auth::user();
     
             // Check if the tenant already has an existing move-out request
             $existingRequest = MoveOutRequest::where('tenant_id', $tenant->id)
                                              ->where('status', '!=', 'deleted') // Exclude deleted requests if needed
                                              ->first();
     
             if ($existingRequest) {
                 // If a move-out request already exists, return a response with an error message
                 return response()->json([
                     'message' => 'You can only create one move-out request.',
                 ], 400);
             }
     
             // Create a new move-out request record
             $moveOutRequest = MoveOutRequest::create([
                 'tenant_id' => $tenant->id,
                 'move_out_date' => $request->move_out_date,
                 'move_out_reason' => $request->move_out_reason,
                 'status' => 'pending',  // Default status, can be updated later (pending, approved, rejected)
             ]);
     
             // Get the landlord for the tenant's property
             $landlord = $tenant->property->landlord;
     
             // Send notification to the tenant who created the move-out request
             Notifications::create([
                 'user_type' => 'tenant',  // Add user_type here
                 'user_id' => $tenant->id,
                 'message' => "Your move-out request has been submitted successfully. We will review it soon.",
                 'status' => 'unread',
             ]);
     
             // Send notification to the landlord
             if ($landlord) {
                 Notifications::create([
                     'user_type' => 'landlord',  // Add user_type here
                     'user_id' => $landlord->id,
                     'message' => "Tenant {$tenant->name} has submitted a move-out request for property {$tenant->property->house_no}.",
                     'status' => 'unread',
                 ]);
             }
     
             // Send notification to all admins
             $admins = Admin::all();
             foreach ($admins as $admin) {
                 Notifications::create([
                     'user_type' => 'admin',  // Add user_type here
                     'user_id' => $admin->id,
                     'message' => "Tenant {$tenant->name} has submitted a move-out request for property {$tenant->property->house_no}.",
                     'status' => 'unread',
                 ]);
             }
             // Send notification to all superAdmins
             $admins = SuperAdmin::all();
             foreach ($admins as $admin) {
                 Notifications::create([
                     'user_type' => 'admin',  // Add user_type here
                     'user_id' => $admin->id,
                     'message' => "Tenant {$tenant->name} has submitted a move-out request for property {$tenant->property->house_no}.",
                     'status' => 'unread',
                 ]);
             }
     
             // Return the newly created move-out request as a JSON response
             return response()->json($moveOutRequest, 201);
     
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => false,
                 'message' => $th->getMessage()
             ], 500);
         }
     }
     

    /**
     * Display the specified move-out request.
     *
     * @param \App\Models\MoveOutRequest $moveOutRequest
     * @return \Illuminate\Http\Response
     */
    public function show(MoveOutRequest $moveOutRequest)
    {
        // Return the specific move-out request as a JSON response
        return response()->json($moveOutRequest, 200);
    }

    /**
     * Update the specified move-out request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\MoveOutRequest $moveOutRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MoveOutRequest $moveOutRequest)
    {
        try {
            $request->validate([
                'status' => 'required|in:approved,rejected'
            ]);

            $previousStatus = $moveOutRequest->status;
            $newStatus = $request->status;

            // Update status
            $moveOutRequest->update(['status' => $newStatus]);

            // Get the tenant
            $tenant = $moveOutRequest->tenant;

            // Notification messages
            $message = $newStatus == 'approved' 
                ? "Your move-out request has been approved." 
                : "Your move-out request has been denied.";

            // Send notification to tenant
            Notifications::create([
                'user_type' => 'tenant',
                'user_id' => $tenant->id,
                'message' => $message,
                'status' => 'unread',
            ]);

            // If denied, delete the request (optional)
            if ($newStatus == 'rejected') {
                $moveOutRequest->delete();
            }

            return response()->json([
                'message' => "Move-out request {$newStatus} successfully",
                'data' => $moveOutRequest
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified move-out request.
     *
     * @param \App\Models\MoveOutRequest $moveOutRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(MoveOutRequest $moveOutRequest)
    {
        // Delete the move-out request
        $moveOutRequest->delete();

        // Return a success message in the response
        return response()->json(['message' => 'Move-out request deleted successfully'], 200);
    }

    public function indexByProperty(Request $request)
    {
        $propertyId = $request->query('property_id');
        
        $moveOutRequests = MoveOutRequest::with(['tenant'])
            ->whereHas('tenant', function($query) use ($propertyId) {
                $query->where('property_id', $propertyId);
            })
            ->get();

        return response()->json($moveOutRequests);
    }
}
