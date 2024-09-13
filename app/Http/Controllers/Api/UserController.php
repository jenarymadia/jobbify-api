<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user",
     *     summary="Get user and company details",
     *     tags={"User"},
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User and company details",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="birthday", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *                 @OA\Property(property="address", type="string", example="123 Main St"),
     *                 @OA\Property(property="address_line_2", type="string", example="Apt 4B"),
     *                 @OA\Property(property="city", type="string", example="Anytown"),
     *                 @OA\Property(property="postal_code", type="string", example="12345"),
     *                 @OA\Property(property="country", type="string", example="USA")
     *             ),
     *             @OA\Property(property="company", type="object",
     *                 @OA\Property(property="business", type="string", example="Acme Corp"),
     *                 @OA\Property(property="phone_number", type="string", example="1234567890"),
     *                 @OA\Property(property="staffs_no", type="integer", example=25),
     *                 @OA\Property(property="current_revenue", type="number", format="float", example=500000),
     *                 @OA\Property(property="company_name", type="string", example="Acme Corp")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index(){
        $user = Auth::user();

        $company = $user->personalTeam(); // Assuming personalTeam relationship is set up correctly
            
        // Fetch the company details using the team's ID
        $companyDetails = CompanyDetails::select(
            "business_name as business",
            "business_number as phone_number",
            "staffs_no",
            "current_revenue"
        )
        ->where('team_id', $company->id)->first();
    
        $companyDetails = $companyDetails ? $companyDetails->toArray() : [];
    
        // Decode specific fields if needed

        $userData = $user->only([
            'first_name',
            'last_name',
            'birthday',
            'email',
            'street_line_1',
            'street_line_2 ',
            'city',
            'zip_code',
            'country'
        ]);

        $companyDetails['staffs_no'] = json_decode($companyDetails['staffs_no']);
        $companyDetails['current_revenue'] = json_decode($companyDetails['current_revenue']);
        $companyDetails['company_name'] = $company->name;
    
        return [
            "user" => $userData,
            "company" => $companyDetails
        ];
    }
}
