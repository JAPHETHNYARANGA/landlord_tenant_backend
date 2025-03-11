<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Landlord;
use App\Models\ServiceProvider;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Find the user by email
            $user = $this->findUserByEmail($request->email);

            if ($user && Hash::check($request->password, $user->password)) {
                // Create a new token for the user if using token-based auth
                $token = $user->createToken('authentication')->plainTextToken;

                return response()->json([
                    'success' => true,
                    'user_type' => $user->user_type, // Return the user type
                    'user' => $user,
                    'token' => $token, // Include token if using token-based auth
                ]);
            }

            return response()->json(['error' => 'Invalid credentials'], 401);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Find a user by their email address.
     *
     * @param  string  $email
     * @return mixed
     */
    private function findUserByEmail($email)
    {
        try {
            $user = null;
            $userType = null;

            // Define user types in an array for iteration
            $userTypes = [
                'landlord' => Landlord::class,
                'tenant' => Tenant::class,
                'admin' => Admin::class,
                'superAdmin' =>SuperAdmin::class,
                'serviceProvider'=>ServiceProvider::class
            ];

            foreach ($userTypes as $type => $model) {
                $user = $model::where('email', $email)->first();
                if ($user) {
                    $userType = $type;
                    break;
                }
            }

            if ($user) {
                $user->user_type = $userType; // Set user type to found type
                return $user;
            }

            return null;

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }


    public function logout(Request $request)
    {
        try {
            // Get the authenticated user
            $user = $request->user();

            // Revoke the user's token
            $user->tokens()->delete();

            return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

       //forget password
       public function forgotPassword(Request $request)
        {
            try {
                $request->validate([
                    'email' => 'required|email'
                ]);

                // Define user types in an array for iteration
                $userTypes = [
                    'landlord' => Landlord::class,
                    'tenant' => Tenant::class,
                    'admin' => Admin::class,
                    'superAdmin' => SuperAdmin::class,
                    'serviceProvider'=>ServiceProvider::class
                ];

                $user = null;
                $userType = null;

                // Check each user type to find the user
                foreach ($userTypes as $type => $model) {
                    $user = $model::where('email', $request->email)->first();
                    if ($user) {
                        $userType = $type;
                        break;
                    }
                }

                if (!$user) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email not found'
                    ], 404);
                }

                // Generate a password reset token
                $token = Str::random(60);

                // Save token in password_creates table with user type
                DB::table('password_creates')->updateOrInsert(
                    ['email' => $user->email],
                    [
                        'email' => $user->email,
                        'token' => $token,
                        'user_type' => $userType // Add user type here
                    ]
                );

                // Send password reset link to user's email
                Mail::send('password_reset', ['token' => $token], function ($m) use ($user) {
                    $m->from('info@landlordtenant.com', 'LandlordTenant');
                    $m->to($user->email, $user->name)->subject('Reset Password');
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Password reset link sent to your email'
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'An error occurred',
                    'error' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }


   
       public function resetPassword(Request $request)
        {
            $request->validate([
                'password' => 'required|confirmed',
                'token' => 'required|string'
            ]);

            $tokenData = DB::table('password_creates')->where('token', $request->token)->first();

            if (!$tokenData) {
                return view('PasswordResponse', [
                    'success' => false,
                    'message' => 'Invalid token'
                ]);
            }

            // Check user based on user type
            $user = null;
            switch ($tokenData->user_type) {
                case 'admin':
                    $user = Admin::where('email', $tokenData->email)->first();
                    break;
                case 'landlord':
                    $user = Landlord::where('email', $tokenData->email)->first();
                    break;
                case 'tenant':
                    $user = Tenant::where('email', $tokenData->email)->first();
                    break;
                case 'serviceProvider':
                    $user = ServiceProvider::where('email', $tokenData->email)->first(); 
                    break;   
                default:
                    return view('PasswordResponse', [
                        'success' => false,
                        'message' => 'User type not recognized'
                    ]);
            }

            if (!$user) {
                return view('PasswordResponse', [
                    'success' => false,
                    'message' => 'Email not found'
                ]);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            // Clean up the token
            DB::table('password_creates')->where('email', $user->email)->delete();

            return view('PasswordResponse', [
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
        }

       
     


      public function createPassword(Request $request)
      {
         
          // Retrieve the email based on the token
          $tokenData = DB::table('password_creates')->where('token', $request->token)->first();
      
          if (!$tokenData) {
              return abort(404, 'Invalid token');
          }
      
          return view('set_password', ['token' => $request->token, 'email' => $tokenData->email]);
      }



      public function fetchUser()
      {
          try {
              // Get the authenticated user
              $user = Auth::user();
      
              // Check if the user exists
              if (!$user) {
                  return response()->json(['status' => false, 'message' => 'User not authenticated.'], 401);
              }
      
              // Determine the user type
              $userType = null;
      
              if ($user instanceof Admin) {
                  $userType = 'admin';
              } elseif ($user instanceof Landlord) {
                  $userType = 'landlord';
              } elseif ($user instanceof Tenant) {
                  $userType = 'tenant';
                  // Fetch the tenant with property data
                  $user = $user->load(['property.roomTypes']);
              } elseif ($user instanceof SuperAdmin) {
                  $userType = 'superAdmin';
              } elseif ($user instanceof ServiceProvider) {
                  $userType = 'serviceProvider';
      
                  // Fetch ratings and reviews (eager loading)
                  $user->load('ratings');
      
                  // Optionally, you can include the average rating and total ratings
                  $user->average_rating = $user->averageRating();
                  $user->total_ratings = $user->totalRatings();
                  
              }
      
              return response()->json([
                  'success' => true,
                  'user_type' => $userType, // Return the user type
                  'user' => $user, // Return the user data
              ]);
          } catch (\Throwable $th) {
              return response()->json([
                  'status' => false,
                  'message' => $th->getMessage(),
              ], 500);
          }
      }
}
