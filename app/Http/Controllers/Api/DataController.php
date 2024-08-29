<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class DataController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/roles",
     *     summary="Get a list of roles",
     *     tags={"Roles"},
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of roles",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "1" : "Admin",
     *                 "2" : "Editor",
     *                 "3" : "Viewer"
     *             },
     *             @OA\AdditionalProperties(
     *                 type="integer",
     *                 description="The ID of the role"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving roles")
     *         )
     *     )
     * )
     */
    public function index()
    {
        return Role::pluck("name", "id");
    }
}
