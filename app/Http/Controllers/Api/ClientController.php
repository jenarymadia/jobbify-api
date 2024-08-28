<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\ClientTag;
use App\Models\Status;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearer_token",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class ClientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/clients",
     *     summary="Get a paginated list of clients",
     *     tags={"Clients"},
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
        $clients = Client::paginate(50); // 50 items per page
        return response()->json($clients);
    }

    /**
     * @OA\Get(
     *     path="/api/clients/statuses",
     *     summary="Get statuses for clients",
     *     tags={"Clients"},
     *     security={{"bearer_token":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\AdditionalProperties(
     *                 type="string",
     *                 example="Active"
     *             )
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
    public function statuses()
    {
        return Status::where("module", "lead")->pluck("key", "value");
    }


    /**
     * @OA\Post(
     *     path="/api/clients",
     *     summary="Create a new client",
     *     tags={"Clients"},
     *     security={{"bearer_token":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="johndoe@example.com"),
     *             @OA\Property(property="mobile_no", type="string", example="1234567890"),
     *             @OA\Property(property="street_address", type="string", example="123 Main St"),
     *             @OA\Property(property="city", type="string", example="Springfield"),
     *             @OA\Property(property="region", type="string", example="Illinois"),
     *             @OA\Property(property="postal_code", type="string", example="62704"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="note", type="string", example="This is a note."),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"VIP", "New"}),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Client created successfully"),
     *             @OA\Property(property="client", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="An error occurred while creating the client",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while creating the client"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:clients',
                'mobile_no' => 'required|numeric',
                'street_address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'region' => 'required|string|max:255',
                'postal_code' => 'required|numeric',
                'status' => 'required|integer', // Assuming status is an integer
                'note' => 'nullable|string|max:1000',  // Optional field
                'tags' => 'sometimes|array',  // Optional tags array
                'tags.*' => 'string|max:255', // Each tag should be a string
            ]);

            // Create a new client with the validated data
            $client = Client::create(Arr::except($validatedData, ['tags']));

            // Check if tags are provided and attach them to the client
            if (!empty($validatedData['tags'])) {
                foreach ($validatedData['tags'] as $tag) {
                    ClientTag::create([
                        'tag' => $tag,
                        'client_id' => $client->id,
                    ]);
                }
            }

            // Return a JSON response indicating success
            return response()->json([
                'message' => 'Client created successfully',
                'client' => $client,
            ], 201);

        } catch (ValidationException $e) {
            // Return validation errors
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Return a generic error message
            return response()->json([
                'message' => 'An error occurred while creating the client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Put(
     *     path="/api/clients/{id}",
     *     summary="Update an existing client",
     *     security={{"bearer_token":{}}},
     *     tags={"Clients"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Client ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "email", "mobile_no", "street_address", "city", "region", "postal_code", "status"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
     *             @OA\Property(property="mobile_no", type="string", example="1234567890"),
     *             @OA\Property(property="street_address", type="string", example="123 Elm Street"),
     *             @OA\Property(property="city", type="string", example="Springfield"),
     *             @OA\Property(property="region", type="string", example="IL"),
     *             @OA\Property(property="postal_code", type="string", example="62701"),
     *             @OA\Property(property="status", type="integer", example=1),
     *             @OA\Property(property="note", type="string", example="Client has a special request"),
     *             @OA\Property(property="tags", type="array", @OA\Items(type="string"), example={"tag1", "tag2"})
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client updated successfully"),
     *             @OA\Property(property="client", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="mobile_no", type="string", example="1234567890"),
     *                 @OA\Property(property="street_address", type="string", example="123 Elm Street"),
     *                 @OA\Property(property="city", type="string", example="Springfield"),
     *                 @OA\Property(property="region", type="string", example="IL"),
     *                 @OA\Property(property="postal_code", type="string", example="62701"),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="note", type="string", example="Client has a special request")
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
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
     *             @OA\Property(property="message", type="string", example="An unexpected error occurred."),
     *             @OA\Property(property="error", type="string", example="Error details")
     *         )
     *     )
     * )
     */
    public function update(Client $client, Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:clients,email,' . $client->id,
                'mobile_no' => 'required|numeric',
                'street_address' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'region' => 'required|string|max:255',
                'postal_code' => 'required|numeric',
                'status' => 'required|integer', // Assuming status is an integer
                'note' => 'nullable|string|max:1000',  // Optional field
                'tags' => 'sometimes|array',  // Optional tags array
                'tags.*' => 'string|max:255', // Each tag should be a string
            ]);
    
            // Update the client with the validated data
            $client->update(Arr::except($validatedData, ['tags']));
    
            // Remove existing tags and add new ones
            $client->tags()->delete();
            if ($request->has('tags')) {
                foreach ($request->tags as $tag) {
                    ClientTag::create([
                        'tag' => $tag,
                        'client_id' => $client->id,
                    ]);
                }
            }
    
            // Return a JSON response indicating success
            return response()->json([
                'status' => true,
                'message' => 'Client updated successfully',
                'client' => $client,
            ], 200);
    
        } catch (ValidationException $e) {
            // Return validation errors
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
    
        } catch (\Exception $e) {
            // Return a generic error message
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/clients/{id}",
     *     summary="Delete a client",
     *     tags={"Clients"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="The ID of the client to delete"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Client deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Client not found")
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
    public function destroy(Client $client)
    {
        try {
            // Delete associated tags
            $client->tags()->delete();

            // Delete the client
            $client->delete();

            return response()->json([
                'status' => true,
                'message' => 'Client and associated tags deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An unexpected error occurred.',
            ], 500);
        }
    }
    
}
