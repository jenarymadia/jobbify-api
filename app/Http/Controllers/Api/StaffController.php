<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

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
        $staffs = Auth::user()->personalTeam()->users()->paginate(50);
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
        $team = Auth::user()->personalTeam();
        $staff = $team->users()->find($id);

        if (!$staff) {
            return response()->json(['message' => 'Staff member not found'], 404);
        }

        return response()->json($staff);
    }


    /**
     * @OA\Post(
     *     path="/api/staffs",
     *     summary="Create a new staff member",
     *     tags={"Staffs"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "mobile_no", "role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *             @OA\Property(property="mobile_no", type="string", example="1234567890"),
     *             @OA\Property(property="role", type="integer", example=1),
     *             @OA\Property(property="note", type="string", example="This is a note about the staff member")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Staff successfully created",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Staff successfully created"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *                 @OA\Property(property="mobile_no", type="string", example="1234567890"),
     *                 @OA\Property(property="note", type="string", example="This is a note about the staff member"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-08-29T12:34:56.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-08-29T12:34:56.000000Z")
     *             ),
     *             @OA\Property(property="reset_url", type="string", example="http://your-app-url.com/password/reset?token=abc123&email=johndoe%40example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error")
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
     *             @OA\Property(property="message", type="string", example="An error occurred while creating the staff member")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        // Validate request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile_no' => 'required|string|max:20',
            'role' => 'required|exists:roles,id',
            'note' => 'nullable|string',
        ]);

        $initialPassword = Str::random(12);

        // Create the user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'mobile_no' => $validatedData['mobile_no'],
            'note' => $validatedData['note'] ?? null,
            'password' => Hash::make($initialPassword), // Generate a random password with 12 characters
        ]);

        // Assign role to the user
        $role = Role::findOrFail($validatedData['role']);
        $user->assignRole($role);

        // Attach the user to the personal team of the authenticated user
        $personalTeam = Auth::user()->personalTeam();
        $personalTeam->users()->attach($user->id, [
            'role' => $role->name
        ]);

        // Generate a password reset token
        $token = Password::createToken($user);

        // Create the password reset URL
        $resetUrl = url(config('app.url') . route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        // Send the custom set password email
        // Mail::to($user->email)->send(new CustomSetPassword($resetUrl, $initialPassword));

        // Return a success response
        return response()->json([
            'message' => __('Staff successfully created'),
            'user' => $user,
            'reset_url' => $resetUrl,
        ], 201);
    }


    /**
     * @OA\Put(
     *     path="/api/staffs/{id}",
     *     summary="Update an existing staff member",
     *     tags={"Staffs"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the staff member to update"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "mobile_no", "role"},
     *             @OA\Property(property="name", type="string", example="Jane Doe"),
     *             @OA\Property(property="email", type="string", example="janedoe@example.com"),
     *             @OA\Property(property="mobile_no", type="string", example="9876543210"),
     *             @OA\Property(property="role", type="integer", example=2),
     *             @OA\Property(property="note", type="string", example="Updated note"),
     *             @OA\Property(property="password", type="string", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Staff successfully updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Staff successfully updated"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Jane Doe"),
     *                 @OA\Property(property="email", type="string", example="janedoe@example.com"),
     *                 @OA\Property(property="mobile_no", type="string", example="9876543210"),
     *                 @OA\Property(property="note", type="string", example="Updated note"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2024-08-29T12:34:56.000000Z"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2024-08-29T12:35:56.000000Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error")
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
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Staff not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while updating the staff member")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        // Validate request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'mobile_no' => 'required|string|max:20',
            'role' => 'required|exists:roles,id',
            'note' => 'nullable|string',
            'password' => 'nullable|string|min:8',
        ]);

        // Retrieve the user by their ID
        $user = User::findOrFail($id);

        // Update the user details
        $user->update([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'mobile_no' => $validatedData['mobile_no'],
            'note' => $validatedData['note'] ?? $user->note,
            'password' => !empty($validatedData['password']) ? Hash::make($validatedData['password']) : $user->password,
        ]);

        // Assign the role to the user
        $role = Role::findOrFail($validatedData['role']);
        $user->syncRoles([$role->name]);

        // Attach the user to the personal team
        $personalTeam = Auth::user()->personalTeam();
        $personalTeam->users()->syncWithoutDetaching([
            $user->id => ['role' => $role->name]
        ]);

        // Return a success response
        return response()->json([
            'message' => __('Staff successfully updated'),
            'user' => $user,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/staffs/{id}",
     *     summary="Delete a staff member",
     *     tags={"Staffs"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the staff member to delete"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Staff successfully deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Staff successfully deleted")
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
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Staff not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while deleting the staff member")
     *         )
     *     )
     * )
     */
    public function destroy(User $user)
    {
        // Delete the user from all teams
        Team::where('user_id', $user->id)->delete();
        
        // Delete the user
        $user->delete();

        // Return a success response
        return response()->json([
            'message' => __('Staff successfully deleted'),
        ], 200);
    }
}
