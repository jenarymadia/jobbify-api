<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/staffs",
     *     summary="Get a paginated list of staffs",
     *     tags={"Staffs"},
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object"
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $staffs = Auth::user()->personalTeam->users()->paginate(50);
        return response()->json($staffs);
    }

    /**
     * @OA\Get(
     *     path="/api/staffs/{id}",
     *     summary="Get a specific staff member by ID",
     *     tags={"Staffs"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the staff member",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Staff member not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Staff member not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $team = Auth::user()->personalTeam;
        $staff = $team->users()->find($id);

        if (!$staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }

        return response()->json($staff);
    }
}
