<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceTicket;
use App\Models\ProviderRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProviderRatingController extends Controller
{
    public function submitRating(Request $request, $ticket_id)
    {
        try {
            $tenant = Auth::user();
            // Validate the rating and comment
            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'string|max:1000',
            ]);

            // Find the ticket by ID
            $ticket = MaintenanceTicket::findOrFail($ticket_id);

            // Check if the tenant is the one who created the ticket
            if ($ticket->tenant_id !== $tenant->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can only rate your own tickets.',
                ], 403);
            }

            // Check if the tenant has already rated the ticket
            $existingRating = ProviderRating::where('ticket_id', $ticket_id)
                                             ->where('user_id', $request->user()->id)
                                             ->first();

            if ($existingRating) {
                return response()->json([
                    'status' => false,
                    'message' => 'You have already rated this service provider.',
                ], 400);
            }

            

            // Save the rating and comment
            $rating = new ProviderRating([
                'ticket_id' => $ticket_id,
                'user_id' => $tenant->id,
                'service_provider_id' => $ticket->closed_by_service_provider_id,  // Assuming service_provider_id is in the ticket
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
            $rating->save();

            return response()->json([
                'status' => true,
                'message' => 'Thank you for your feedback!',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }

        
    }

    public function fetchReviews($ticket_id)
    {
        try {
            // Get reviews for the ticket
            $reviews = ProviderRating::where('ticket_id', $ticket_id)
                ->with('tenant') // Assuming you want to include reviewer details
                ->get();

            // If no reviews, return a message
            if ($reviews->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No reviews found for this ticket.',
                ], 404);
            }

            // Return the reviews
            return response()->json($reviews);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
