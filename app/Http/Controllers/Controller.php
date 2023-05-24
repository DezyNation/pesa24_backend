<?php

namespace App\Http\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function token()
    {
        $key = env('JWT_KEY');
        $payload = [
            'timestamp' => time(),
            'partnerId' => env('PAYSPRINT_PARTNERID'),
            'reqid' => abs(crc32(uniqid()))
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    public function index()
    {
        $data = DB::table('services')->where(['is_active' => 1, 'can_subscribe' => 1])->get(['id', 'type', 'service_name', 'operator_name', 'image_url', 'price']);
        return $data;
    }

    public function apiRecords(string $refernce_id, string $provider, string $response)
    {
        $data = DB::table('api_records')->insert([
            'user_id' => auth()->user()->id,
            'organization_id' => auth()->user()->organization_id,
            'reference_id' => $refernce_id,
            'provider_id' => $provider,
            'response' => $response
        ]);

        return $data;
    }

    public function activateService(Request $request)
    {
        $service_id = $request['id'];
        $service = DB::table('services')->where('id', $service_id)->pluck('price');
        $user = auth()->user();
        $wallet = $user->wallet;
        $final_wallet = $service - $wallet;
        $user_update = User::where('id', $user->id)->update([
            'wallet' => $final_wallet
        ]);
        $activation = DB::table('service_user')->insertGetId([
            'service_id' => $request['id'],
            'user_id' => $user->id,
            'pesa24_active' => 1,
        ]);
    }

    public function transaction(float $amount, string $service, string $service_type, float $user_id, float $opening_balance, string $transaction_id, float $closing_balance, string $metadata, float $credit = 0)
    {
        DB::table('transactions')->insert([
            'debit_amount' => $amount,
            'transaction_for' => $service,
            'user_id' => $user_id,
            'trigered_by' => auth()->user()->id ?? 85,
            'credit_amount' => $credit,
            'opening_balance' => $opening_balance,
            'closing_balance' => $closing_balance,
            'service_type' => $service_type,
            'metadata' => $metadata,
            'transaction_id' => $transaction_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        User::where('id', auth()->user()->id)->update([
            'wallet' => $closing_balance
        ]);

        return response()->json(['message' => 'Transaction successful.']);
    }

    public function onboard()
    {
        $token = $this->token();

        $data = [
            'merchantcode' => "PESA24API".auth()->user()->id,
            'mobile' => auth()->user()->phone_number,
            'is_new' => 0,
            'email' => auth()->user()->email,
            'firm' => auth()->user()->company_name ?? 'PAYMONEY',
            'callback' => 'https://api.pesa24.in/api/onboard-callback-paysprint',
        ];

        $response = Http::withHeaders([
            'Token' => $token,
            'Authorisedkey' => env('AUTHORISED_KEY'),
            'Content-Type: application/json'
        ])->post('https://api.paysprint.in/api/v1/service/onboard/onboard/getonboardurl', $data);
        Log::channel('response')->info($response);
        DB::table('users')->where('id', auth()->user()->id)->update([
            'paysprint_merchant' => $data['merchantcode'],
            'updated_at' => now()
        ]);
        if ($response['status'] == false) {
            return response($response['message'], 400);
        }
        return $response;
    }

    // public function baseCommission(int $amount, int $user_id, int $service_id)
    // {
    //     $result = DB::table('users')
    //         ->join('package_user', 'users.id', '=', 'package_user.user_id')
    //         ->join('packages', 'package_user.package_id', '=', 'packages.id')
    //         ->join('package_service', 'packages.id', '=', 'package_service.package_id')
    //         ->join('service_user', 'users.id',  '=', 'service_user.user_id')
    //         ->join('services', 'package_service.service_id', '=', 'services.id')
    //         ->select('package_service.*', 'services.service_name')
    //         ->where(['service_user.user_id' => $user_id, 'service_user.service_id' => $service_id, 'package_service.service_id' => $service_id, 'package_user.user_id' => $user_id])
    //         ->where('from', '<', $amount)
    //         ->where('to', '>=', $amount)
    //         ->get();

    //         Log::channel('response')->info($result);
    //         if (empty($result)) {
    //             return response()->json(['message' => 'No further commission']);
    //         }
    //     $array = json_decode($result, true);
    //     $user = User::findOrFail($user_id);
    //     $user_role = $user->getRoleNames();
    //     $role_commission = $user_role[0] . "_" . "commission";
    //     $service_name = $array[0]['service_name'];
    //     $flat = $array[0]['is_flat'];
    //     $surcharge = $array[0]['is_surcharge'];
    //     if ($flat) {
    //         $commission = $amount * $array[0]["$role_commission"] / 100;
    //     } else {
    //         $commission = $array[0]["$role_commission"];
    //     }

    //     $opening_balance = $user->wallet;
    //     if ($surcharge) {
    //       $debit = $commission;
    //       $closing_balance = $opening_balance - $debit;
    //       $credit = 0;
    //     } else {
    //         $credit = $commission;
    //         $debit = 0;
    //         $closing_balance = $opening_balance + $credit;
    //     }
        


    //     $user->update([
    //         'wallet' => $closing_balance
    //     ]);
    //     $transaction_id = "COM" . strtoupper(Str::random(5));
    //     $this->transaction($debit, "Commission for $service_name", 'commission', $user_id, $opening_balance, $transaction_id, $closing_balance, $credit);

    //     $parent = DB::table('user_parent')->where('user_id', $user_id);

    //     if ($parent->exists()) {
    //         $parent_id = $parent->pluck('parent_id');
    //         $this->baseCommission($amount, $parent_id[0], $service_id);
    //     }
    // }
}
