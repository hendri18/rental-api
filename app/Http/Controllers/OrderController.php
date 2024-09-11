<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        $query = Order::query();
        $query->leftJoin('cars', 'orders.car_id', '=', 'cars.id');
        $query->leftJoin('customers', 'orders.user_id', '=', 'customers.user_id');
        $query->select('orders.*', 'cars.brand', 'cars.model', 'customers.name');
        if (auth()->user()->hasRole('customer')) {   
            $query->where('user_id', auth()->user()->id);
        }

        if ($search = $request->input('search')) {
            $query->where('cars.brand', 'like', "%{$search}%")
                  ->orWhere('cars.model', 'like', "%{$search}%")
                  ->orWhere('total_price', 'like', "%{$search}%");
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
     * Process to rent a car
     */
    public function order(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_id' => 'required|exists:App\Models\Car,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => implode(" ",$validator->messages()->all()),
            ], 400);
        }

        DB::beginTransaction();
        try {

            $car = Car::findOrFail($request->car_id);
            $start_date = $request->start_date;
            $end_date = $request->end_date;

            $checkAvailability = Order::where('car_id', $car->id)
                ->where('status', 'ongoing')
                ->where('start_date', '>=', $start_date)
                ->where('end_date', '<=', $end_date)
                ->first();

            if ($checkAvailability) {
                return response()->json([
                    "status" => "error",
                    "message" => "Mobil tidak tersedia untuk waktu tersebut",
                ], 404);
            }

            $order = new Order;
            $order->user_id = auth()->user()->id;
            $order->car_id = $car->id;
            $order->start_date = $request->start_date;
            $order->end_date = $request->end_date;
            $order->fixed_rental_rate = $car->rental_rate;
            $order->status = 'ongoing';
            $order->save();

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
            "data" => $order,
        ], 201);   
    }

    /**
     * Return the car based on plate number
     */
    public function returnCar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plate_number' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "status" => "error",
                "message" => implode(" ",$validator->messages()->all()),
            ], 400);
        }

        $user_id = auth()->user()->id;
        $car = Car::where('plate_number', $request->plate_number)->first();
        if (!$car) {
            return response()->json([
                "status" => "error",
                "message" => "Mobil dengan plat nomor tersebut tidak ditemukan",
            ], 404);
        }
        $order = Order::where('user_id', $user_id)
            ->where('car_id', $car->id)
            ->where('status', 'ongoing')
            ->first();

        if (!$order) {
            return response()->json([
                "status" => "error",
                "message" => "Pemesanan dengan plat nomor tersebut tidak ditemukan",
            ], 404);
        }
        DB::beginTransaction();
        try {

            $return_date = date('Y-m-d');
            $startDate = new \DateTime($order->start_date);
            $endDate = new \DateTime($return_date);
            $interval = $startDate->diff($endDate);
            $total_days = $interval->days;
            $total_days += 1;

            $order->return_date = $return_date;
            $order->total_days = $total_days;
            $order->total_price = $total_days * $order->fixed_rental_rate;
            $order->status = 'completed';
            $order->save();

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
            "data" => $order,
        ], 201);




    }
}
