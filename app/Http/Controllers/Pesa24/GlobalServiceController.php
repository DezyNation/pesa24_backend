<?php

namespace App\Http\Controllers\Pesa24;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class GlobalServiceController extends Controller
{
    public function manageService(Request $request)
    {

        $request->validate([
            'is_active' => 'required',
            'down_message' => 'required_if:can_subscribe,0', 'required_if:is_active,0'
        ]);

        $table = DB::table('services')->where('id', $request['id'])->update([
            'service_name' => $request['service_name'],
            'image_url' => $request['image_url'],
            'is_active' => $request['is_active'],
            'api_call' => $request['api_call'],
            'down_message' => $request['down_message'],
            'updated_at' => now()
        ]);

        return $table;
    }

    public function getServices()
    {
        $data = DB::table('services')->get();
        return $data;
    }

    public function createService(Request $request)
    {
        $request->validate([
            'service_name' => 'required',
            'price' => 'required',
            'eko_id' => 'required|integer',
            'paysprint_id' => 'required|integer',
            'type' => 'required|string',
            'is_active' => 'required',
            'api_call' => 'required',
            'can_subscribe' => 'required',
            'image_url' => 'required'

        ]);

        $data = DB::table('services')->insert([
            'service_name' => $request['service_name'],
            'price' => $request['price'],
            'eko_id' => $request['eko_id'],
            'paysprint_id' => $request['paysprint_id'],
            'type' => $request['type'],
            'is_active' => $request['is_active'],
            'api_call' => $request['api_call'],
            'can_subscribe' => $request['can_subscribe'],
            'image_url' => $request['image_url'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $data;
    }

    public function getCategories()
    {
        $data = DB::table('categories')->get();
        return $data;
    }

    public function createCategories(Request $request)
    {
        $data = DB::table('categories')->insert([
            'name' => $request['categoryName'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return $data;
    }

    public function deleteCategory(Request $request)
    {
        DB::table('categories')->where('id', $request['categoryId'])->delete();
        return true;
    }

    public function registerOperators(Request $request)
    {

        $request->validate([
            'categoryId' => 'required|exists:categories,id',
            'paysprintId' => 'required|integer',
            'ekoId' => 'required|integer',
            'operatorName' => 'required|string',
        ]);

        DB::table('operators')->updateOrInsert(
            [
                'category_id' => $request['categoryId'],
                'paysprint_id' => $request['paysprintId'],
                'eko_id' => $request['ekoId'],
            ],
            [
                'type' => $request['type'],
                'name' => $request['operatorName'],
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        return true;
    }

    public function getOperators()
    {
        $data = DB::table('operators')
            ->join('categories', 'categories.id', '=', 'operators.category_id')
            ->select('operators.*', 'categories.name as category_name')
            ->get();

        return $data;
    }

    public function deleteOperator(Request $request)
    {
        DB::table('operators')->where('id', $request['operatorId'])->delete();
        return true;
    }
}
