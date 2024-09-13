<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
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
        $companyDetails['staffs_no'] = json_decode($companyDetails['staffs_no']);
        $companyDetails['current_revenue'] = json_decode($companyDetails['current_revenue']);
        $companyDetails['company_name'] = $company->name;
    
        $userData = $user->only([
            'first_name',
            'last_name',
            'birthday',
            'email',
            'street_line_1 as address',
            'street_line_2 as address_line_2',
            'city',
            'zip_code as postal_code',
            'country'
        ]);
    
        return [
            "user" => $userData,
            "company" => $companyDetails
        ];
    }
}
