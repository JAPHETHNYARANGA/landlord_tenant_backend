<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Landlord;
use App\Models\Notifications;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
       /**
     * Send a notification to a user
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function sendNotification(Request $request)
    {
        // Validate the incoming data
        $request->validate([
            'user_id' => 'required|exists:tenants,id|exists:landlords,id|exists:admins,id', // Ensure the user exists
            'user_type' => 'required|in:tenant,landlord,admin', // Validate the user type
            'message' => 'required|string|max:255',
        ]);

        // Dynamically fetch the user based on user_type
        $user = null;
        if ($request->user_type == 'tenant') {
            $user = Tenant::find($request->user_id);
        } elseif ($request->user_type == 'landlord') {
            $user = Landlord::find($request->user_id);
        } elseif ($request->user_type == 'admin') {
            $user = Admin::find($request->user_id);
        }

        // Ensure user exists
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Create the notification for the specific user
        $notification = Notifications::create([
            'user_type' => $request->user_type,
            'user_id' => $user->id,
            'message' => $request->message,
            'status' => 'unread',  // Default to 'unread'
        ]);

        return response()->json([
            'message' => 'Notification sent successfully',
            'notification' => $notification,
        ], 201);
    }

    /**
     * Get notifications for the authenticated user
     */
    /**
     * Get notifications for the authenticated user
     */
    public function getNotifications()
    {
        try {
            $user = Auth::user();

            // Determine the user type explicitly by checking the model instance
            if ($user instanceof Tenant) {
                $userType = 'tenant';
            } elseif ($user instanceof Landlord) {
                $userType = 'landlord';
            } elseif ($user instanceof Admin) {
                $userType = 'admin';
            }  elseif ($user instanceof SuperAdmin){
                $userType = 'admin';
            } else {
                // In case the user is not of any expected type (shouldn't happen)
                return response()->json([
                    'status' => false,
                    'message' => 'Unknown user type.',
                ], 400);
            }

            // Fetch notifications filtered by user_id and user_type
            $notifications = Notifications::where('user_id', $user->id)
                                        ->where('user_type', $userType)  // Use the determined user type
                                        ->get();

            return response()->json([
                'notifications' => $notifications,
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

}
