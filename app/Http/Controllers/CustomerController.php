<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();
        
        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%");
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

    /**
     * update a newly created resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone_number' => 'required',
            'license_number' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => implode(" ",$validator->messages()->all()),
            ], 400);
        }

        DB::beginTransaction();
        try {
            
            $customer = Customer::findOrFail($id);
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
            "status" => "success",
            "data" => $customer,
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $customer = Customer::findOrFail($id);
            $customer->delete();
            DB::commit();
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage()
            ], 500);
            DB::rollback();
        }
    }
}
