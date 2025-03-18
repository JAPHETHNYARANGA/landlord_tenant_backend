<?php

namespace App\Http\Controllers;

use App\Models\Landlord;
use App\Models\RentPayments;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RentPaymentController extends Controller
{
    /**
     * Fetch rent payments made by a specific tenant.
     *
     * @param int $tenantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchTenantRentPayments($tenantId)
    {
        // Find the tenant
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Tenant not found.',
            ], 404);
        }

        // Fetch rent payments made by the tenant
        $rentPayments = RentPayments::where('tenant_id', $tenantId)->get();

        return response()->json([
            'status' => 'success',
            'data' => $rentPayments,
        ]);
    }

    /**
     * Fetch all tenants who have paid rent.
     *
     * @return \Illuminate\Http\JsonResponse
     */



    public function fetchAllTenants()
    {
        try {
            // Get the current month (format: YYYY-MM)
            $currentMonth = now()->format('Y-m');

            // Fetch tenants who have made at least one rent payment this month
            $tenants = Tenant::whereHas('rentPayments', function ($query) use ($currentMonth) {
                // Filter rent payments for the current month
                $query->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year);
            })
                ->with(['rentPayments' => function ($query) use ($currentMonth) {
                    // Get the latest rent payment of the current month
                    $query->whereMonth('payment_date', now()->month)
                        ->whereYear('payment_date', now()->year)
                        ->latest('payment_date') // Get the latest payment first
                        ->take(1); // We only need the latest payment
                }, 'property']) // Include the property details
                ->get();

            // Prepare the response data
            $data = $tenants->map(function ($tenant) {
                // Check if there is a rent payment for this tenant this month
                $payment = $tenant->rentPayments->first();

                // If payment exists, use Carbon to format the payment_date
                $paymentDate = $payment && $payment->payment_date
                    ? Carbon::parse($payment->payment_date)->format('Y-m-d') // Parse and format the payment date
                    : null;

                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'property_name' => $tenant->property ? $tenant->property->name : 'N/A', // Get property name
                    'amount' => $payment ? $payment->amount : 0, // If payment exists, show amount, else 0
                    'payment_date' => $paymentDate, // Use formatted payment date or null
                    'status' => $payment ? 'Paid' : 'Not Paid', // If payment exists, status is "Paid", else "Not Paid"
                ];
            });

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }




    /**
     * Fetch tenants related to a specific landlord who have paid rent.
     *
     * @param int $landlordId
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchLandlordTenantsWhoPaidRent($landlordId)
    {
        try {
            // Find the landlord
            $landlord = Landlord::find($landlordId);

            if (!$landlord) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Landlord not found.',
                ], 404);
            }

            // Get the current month and year
            $currentMonth = now()->format('m');
            $currentYear = now()->format('Y');

            // Fetch tenants associated with the landlord's properties who have paid rent this month
            $tenants = Tenant::with('property', 'rentPayments')  // Eager load the 'property' and 'rentPayments' relationships
                ->whereHas('property', function ($query) use ($landlordId) {
                    $query->where('landlord_id', $landlordId);
                })
                ->whereHas('rentPayments', function ($query) use ($currentMonth, $currentYear) {
                    $query->whereMonth('payment_date', $currentMonth)
                        ->whereYear('payment_date', $currentYear);
                })
                ->get();

            // Map tenants to include the property name, amount, and payment status in the response
            $tenantsWithPropertyName = $tenants->map(function ($tenant) {
                // Find the latest rent payment (assuming only one payment per month)
                $latestPayment = $tenant->rentPayments->last();  // Get the most recent payment

                // Add the property name and amount to the tenant data
                $tenant->property_name = $tenant->property->name;
                $tenant->amount = $latestPayment ? $latestPayment->amount : 0;  // If no payment, set amount to 0
                $tenant->status = 'Paid';  // Set status as 'Paid' since they have made a payment
                return $tenant;
            });

            return response()->json([
                'status' => 'success',
                'data' => $tenantsWithPropertyName,  // Return the updated tenants data
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
