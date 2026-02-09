<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get all users except the current user
        $users = User::where('id', '!=', $user->id)
            ->select(['id', 'name', 'email', 'created_at'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ], 200);
    }

    public function search(Request $request): JsonResponse
    {
        $term = $request->input('term');
        
        if (empty($term)) {
            return response()->json([
                'success' => true,
                'data' => [],
            ], 200);
        }

        $user = $request->user();

        // Search users excluding current user
        $users = User::where('id', '!=', $user->id)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%");
            })
            ->select(['id', 'user_id', 'name', 'email', 'avatar'])
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ], 200);
    }

    /**
     * Display the specified user.
     * Recherche par ID local ou user_id
     *
     * @param  int  $id  - Peut être l'ID local ou le user_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        // Rechercher par ID local ou user_id
        $user = User::where('id', $id)
            ->orWhere('user_id', $id)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'user_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
        ], 200);
    }
}

