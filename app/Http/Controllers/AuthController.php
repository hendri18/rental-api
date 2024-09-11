<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Customer;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users',
            'address' => 'required|string',
            'phone_number' => 'required|max:15',
            'license_number' => 'required|max:12',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => implode(" ",$validator->messages()->all()),
            ], 400);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $role = Role::where('name', 'customer')->first();
            if (!$role) {
                $role = Role::create(['name' => 'customer']);
            }

            $customer = new Customer;
            $customer->user_id = $user->id;
            $customer->name = $request->name;
            $customer->address = $request->address;
            $customer->phone_number = $request->phone_number;
            $customer->license_number = $request->license_number;
            $customer->save();
            

            DB::commit();
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 500);
            DB::rollback();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function getAdmin(Request $request) {
        $query = User::query();
        $query->role('admin');
        
        if ($search = $request->input('search')) {
            $query->where('email', 'like', "%{$search}%");
        }
    
        // Sorting
        if ($sortField = $request->input('sortField')) {
            $sortOrder = $request->input('sortOrder', 'asc');
            $query->orderBy($sortField, $sortOrder);
        }
    
        // Pagination
        $perPage = $request->input('itemsPerPage', 10);
        $page = $request->input('page', 1);
    
        $data = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            "status" => "success",
            "data" => [
                "items" => $data,
                "total" => $data->total()
            ],
        ]);
    }

    public function registerAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => implode(" ",$validator->messages()->all()),
            ], 400);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $role = Role::where('name', 'admin')->first();
            if (!$role) {
                $role = Role::create(['name' => 'admin']);
            }

            $user->assignRole($role);

            DB::commit();
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 500);
            DB::rollback();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|unique:users',
            'address' => 'required|string',
            'phone_number' => 'required|max:15',
            'license_number' => 'required|max:12',
            'password' => 'required|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => implode(" ",$validator->messages()->all()),
            ], 400);
        }

        DB::beginTransaction();
        try {

            $user = User::findOrFail($id);
            $user->email = $request->email;
            if (!empty($request->password)) {
                $user->password = Hash::make($request->password);
            }
            $user->save();

            $customer = Customer::where('user_id', $user->id)->first();
            $customer->name = $request->name;
            $customer->address = $request->address;
            $customer->phone_number = $request->phone_number;
            $customer->license_number = $request->license_number;
            $customer->save();
            

            DB::commit();
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 500);
            DB::rollback();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'customer' => $customer,
            ],
        ]);
    }

    public function updateAdmin(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255|unique:users',
            'password' => 'nullable|string|min:8'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => implode(" ",$validator->messages()->all()),
            ], 400);
        }

        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);
            $user->email = $request->email;
            if (!empty($request->password)) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            DB::commit();
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 500);
            DB::rollback();
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyAdmin(string $id)
    {
        DB::beginTransaction();
        try {
            $user = User::findOrFail($id);
            $user->delete();
            DB::commit();
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 500);
            DB::rollback();
        }
    }

    public function login(Request $request)
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'User Not Found!'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        $customer = Customer::where('user_id', $user->id)->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->roles->first()->name ?? '',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'customer' => $customer,
            ],
        ]);
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->json([
            'status' => 'success',
            'data' => null,
        ]);
    }
}
