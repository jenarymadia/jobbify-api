<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CompanyDetails;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Info(
 *     title="Jobbify API",
 *     version="0.1",
 *     description="Jobbify API Documentation",
 *     @OA\Contact(
 *         email="support@example.com"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "birthday", "email", "password", "company_name", "staffs_no", "current_revenue", "business", "phone_number", "address", "address_line_2", "city", "postal_code", "country"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="birthday", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="company_name", type="string", example="Doe Inc."),
     *             @OA\Property(property="staffs_no", type="integer", example=50),
     *             @OA\Property(property="current_revenue", type="string", example="500000"),
     *             @OA\Property(property="business", type="string", example="E-commerce"),
     *             @OA\Property(property="phone_number", type="string", example="1234567890"),
     *             @OA\Property(property="address", type="string", example="123 Main St"),
     *             @OA\Property(property="address_line_2", type="string", example="Suite 500"),
     *             @OA\Property(property="city", type="string", example="Los Angeles"),
     *             @OA\Property(property="postal_code", type="string", example="90001"),
     *             @OA\Property(property="country", type="string", example="USA")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User Created Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User Created Successfully"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object", additionalProperties=@OA\Property(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred.")
     *         )
     *     )
     * )
     */
    public function createUser(Request $request)
    {
        try {
            // Validate
            $validateUser = Validator::make($request->all(), 
            [
                'first_name' => 'required',
                'last_name' => 'required',
                'birthday' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
                'company_name' => 'required',
                'staffs_no' => '',
                'current_revenue' => '',
                'business' => '',
                'phone_number' => '',
                'address' => '',
                'address_line_2' => '',
                'city' => '',
                'postal_code' => '',
                'country' => ''
            ]);

            if($validateUser->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'birthday' => $request->birthday,
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $teamID = $this->createTeam($user, $request->company_name); 
            $this->startTrial($user); 
            $this->addCompanyDetails($teamID, $request);

            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'user' => $user,
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user, $teamName = null): int
    {
        $teamName = $teamName ?? explode(' ', $user->name, 2)[0]."'s Team";
        $team = Team::forceCreate([
            'user_id' => $user->id,
            'name' => $teamName,
            'personal_team' => true,
        ]);
        
        // Now you can save the team to the user's ownedTeams
        $user->ownedTeams()->save($team);

        return $team->id;
         
    }

    protected function addCompanyDetails($teamID, $request) {
        CompanyDetails::create([
            "team_id" => $teamID,
            "staffs_no" => $request->staffs_no,
            "current_revenue" => $request->current_revenue,
            "phone_number" => $request->phone_number,
            "business_name" => $request->business,
            "business_number" => $request->phone_number,
            "street_line_1" => $request->address,
            "street_line_2" => $request->address_line2,
            "city" => $request->city,
            "zip_code" => $request->postal,
            "country" => $request->country
        ]);
    }


    protected function startTrial(User $user)
    {
        // Set the trial end date 14 days from now
        $user->trial_ends_at = now()->addDays(14);
        $user->save();
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Login a user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User Logged In Successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="User Logged In Successfully"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Validation error or login failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email & Password does not match with our record."),
     *             @OA\Property(property="errors", type="object", additionalProperties=@OA\Property(type="string"))
     *         )
     *     )
     * )
     */
    public function loginUser(Request $request)
    {
        try {
            $validateUser = Validator::make($request->all(), 
            [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if($validateUser->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            if(!Auth::attempt($request->only(['email', 'password']))){
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'user' => $user,
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

}
