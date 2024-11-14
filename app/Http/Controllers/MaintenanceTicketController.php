<?php

namespace App\Http\Controllers;

use App\Models\Landlord;
use App\Models\MaintenanceTicket;
use App\Models\Properties;
use App\Models\ServiceProvider;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class MaintenanceTicketController extends Controller
{
    // Display a listing of maintenance tickets
    public function index()
    {
        $tickets = MaintenanceTicket::all()->map(function ($ticket) {
            $ticket->image_url = $ticket->image ? asset('storage/' . $ticket->image) : null;
            return $ticket;
        });
        return response()->json($tickets);
    }


    // Store a newly created maintenance ticket
    public function store(Request $request)
    {
        try {
            $request->validate([
                'tenant_id' => 'required|exists:tenants,id',
                'property_id' => 'required|exists:properties,id',
                'issue' => 'required|string|max:255',
                'description' => 'required|string',
                'image' => 'required', // Image validation
            ]);

            // Create a new maintenance ticket
            $ticketData = $request->only(['tenant_id', 'property_id', 'issue', 'description']);

            // Handle image upload
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = $image->store('images/tickets', 'public'); // Store image in storage/app/public/images/tickets

                // Add the image path to ticket data
                $ticketData['image'] = $imagePath;
            }

            $ticket = MaintenanceTicket::create($ticketData);

            $property = Properties::where('id', $ticket->property_id)->first();
            $landlord = Landlord::find($property->landlord_id);

            if ($landlord) {
                Mail::send('ticket_notification', [
                    'ticket' => $ticket,
                    'landlord' => $landlord,
                ], function ($message) use ($landlord) {
                    $message->from('info@landlordtenant.com', 'LandlordTenant');
                    $message->to($landlord->email)->subject('New Maintenance Ticket Created');
                });
            }

            return response()->json([
                'message' => 'Maintenance ticket created successfully.',
                'ticket' => $ticket,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    // Display the specified maintenance ticket
    public function show($id)
    {
        try {
            $ticket = MaintenanceTicket::findOrFail($id);
            $ticket->image_url = $ticket->image ? asset('storage/' . $ticket->image) : null;
            return response()->json($ticket);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    // Update the specified maintenance ticket's status
    public function update(Request $request, $id)
    {
        try {
            $technician = Auth::user();
            $request->validate([
                'ticket_status' => 'nullable|in:pending,in_progress,complete',
                'status' => 'nullable|in:open,closed',
                'technician_notes' => 'nullable|string',
                'priority' => 'nullable|in:low,high', // Validate the priority field (low or high)
            ]);

            // Find the ticket by ID
            $ticket = MaintenanceTicket::findOrFail($id);

            // If 'ticket_status' is provided and is 'complete', automatically mark the ticket as 'closed'
            if ($request->has('ticket_status') && $request->ticket_status === 'complete') {
                $ticket->status = 'closed';  // Mark ticket as closed when completed

                // Set the service provider who is closing the ticket (we assume the provider is logged in and available)
                // If you are using authentication, the service provider's ID should come from the authenticated user
                $ticket->closed_by_service_provider_id = $technician->id;  // Assuming the provider is logged in
            }

            // Update the ticket fields: Only allow technician_notes, ticket_status, status, and priority to be updated
            $ticket->update([
                'status' => $request->status ?? $ticket->status,  // Only update if provided
                'ticket_status' => $request->ticket_status ?? $ticket->ticket_status,  // Only update if provided
                'technician_notes' => $request->technician_notes ?? $ticket->technician_notes,  // Only update if provided
                'priority' => $request->priority ?? $ticket->priority,  // Only update priority if provided
            ]);

            return response()->json([
                'message' => 'Ticket updated successfully.',
                'ticket' => $ticket,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }





    // Remove the specified maintenance ticket from storage
    public function destroy($id)
    {
        try {
            $ticket = MaintenanceTicket::findOrFail($id);

            // Check if there is an image associated with the ticket
            if ($ticket->image) {
                // Define the path to the image
                $imagePath = storage_path('app/public/' . $ticket->image);

                // Check if the file exists and delete it
                if (file_exists($imagePath)) {
                    unlink($imagePath); // Delete the image file
                }
            }

            // Delete the ticket from the database
            $ticket->delete();

            return response()->json(['message' => 'Ticket deleted successfully']);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    public function fetchTenantTickets()
    {
        try {
            // Get the authenticated user's tenant ID
            $tenant_id = Auth::user()->id;

            // Fetch tickets associated with the tenant ID
            $tickets = MaintenanceTicket::where('tenant_id', $tenant_id)->get()->map(function ($ticket) {
                $ticket->image_url = $ticket->image ? asset('storage/' . $ticket->image) : null;

                // Check if the ticket has a rating
                $ticket->has_rating = $ticket->ratings()->exists();  // Add this check

                return $ticket;
            });

            return response()->json($tickets);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }



    public function fetchLandlordTickets()
    {
        try {
            // Get the authenticated landlord's ID
            $landlord_id = Auth::user()->id;

            // Fetch the properties associated with the landlord
            $properties = Properties::where('landlord_id', $landlord_id)->pluck('id');

            // Fetch tickets for the properties
            $tickets = MaintenanceTicket::whereIn('property_id', $properties)->get();

            return response()->json($tickets);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }
}
