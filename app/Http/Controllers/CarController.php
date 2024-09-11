<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class CarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Car::query();
        $start_date = $request->start_date ?? date('Y-m-d');
        $end_date = $request->end_date ?? date('Y-m-d');
        if ($request->available || $request->unavailable) {
            $query->leftJoin('orders', function ($join) use ($start_date, $end_date) {
                $join->on('cars.id', '=', 'orders.car_id')
                    ->where('orders.status', 'ongoing')
                    ->where('orders.start_date', '>=', $start_date)
                    ->where('orders.end_date', '<=', $end_date);
            });

            if ($request->available) {
                $query->whereNull('orders.car_id');
            }
            if ($request->unavailable) {
                $query->whereNotNull('orders.car_id');
            }
        } 

        if ($request->search) {
            $query->where('brand', 'like', '%'.$request->search.'%')
                  ->orWhere('model', 'like', '%'.$request->search.'%');
        }

        return response()->json([
            "status" => "success",
            "data" => $query->select('cars.*')->get(),
        ]);
    }

    public function getCarServerSide(Request $request)
    {
        $query = Car::query();
        
        if ($search = $request->input('search')) {
            $query->where('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('plate_number', 'like', "%{$search}%");
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'plate_number' => 'required|string|max:255',
            'rental_rate' => 'required',
            'image' => 'nullable|mimes:jpeg,jpg,png',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => implode(" ",$validator->messages()->all()),
            ], 400);
        }

        DB::beginTransaction();
        try {
            
            $car = new Car;
            $car->brand = $request->brand;
            $car->model = $request->model;
            $car->plate_number = $request->plate_number;
            $car->rental_rate = $request->rental_rate;

            if ($request->hasFile('image')){
                $request_file = $request->file('image');
                $destinationPath = 'cars';
                $image = time().' - '.$request_file->getClientOriginalName();
                $image_path = $request_file->move(public_path($destinationPath), $image);
                $car->image = 'cars/'.$image;
            }

            $car->save();

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
            "data" => $car,
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'plate_number' => 'required|string|max:255',
            'rental_rate' => 'required',
            'image' => 'nullable|mimes:jpeg,jpg,png',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => implode(" ",$validator->messages()->all()),
            ], 400);
        }

        DB::beginTransaction();
        try {
            
            $car = Car::findOrFail($id);
            $car->brand = $request->brand;
            $car->model = $request->model;
            $car->plate_number = $request->plate_number;
            $car->rental_rate = $request->rental_rate;

            if ($request->hasFile('image')){
                $prev_image_path = public_path($car->image);
                if (File::exists($prev_image_path)) {
                    File::delete($prev_image_path);
                }

                $request_file = $request->file('image');
                $destinationPath = 'cars';
                $image = time().' - '.$request_file->getClientOriginalName();
                $image_path = $request_file->move(public_path($destinationPath), $image);
                $car->image = 'cars/'.$image;
            }

            $car->save();

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
            "data" => $car,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $car = Car::findOrFail($id);
            if (!empty($car->image)){
                $image_path = public_path($car->image);
                if (File::exists($image_path)) {
                    File::delete($image_path);
                }
            }
            $car->delete();
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
