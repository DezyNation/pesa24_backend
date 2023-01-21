<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\v1\UserResource;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return new UserResource(User::with('roles')->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return new UserResource(User::with('roles')->findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'max:255',
            'last_name' => 'max:255',
            'aadhaar' => 'integer|max:12|min:12',
            'pan_number' => 'string|max:10|min:10|regex:[A-Z]{5}[0-9]{4}[A-Z]{1}',
        ]);
        
        $user =  User::find(Auth::id());
        $user->first_name = $request['first_name'];
        $user->last_name = $request['last_name'];
        $user->name = $user->first_name." ".$user->last_name;
        $user->aadhaar = $request['aadhaar'];
        $user->pan_number = $request['pan_number'];

        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function otp(Request $request)
    {
        $otp = rand(1000, 9999);
        $user = User::find(Auth::id());
        $user->update(['otp' => Hash::make($otp)]);
        // $user->otp = ;
        return $otp;
    }

    public function verifyOtp(Request $request)
    {
        $user = User::findOrFail(Auth::id());
        if (! $user || ! Hash::check($request['otp'], $user->otp)) {
            throw ValidationException::withMessages([
                'error' => ['Given details do not match our records.'],
            ]);
        }
        $user->update(['phone_number' => $request['newNumber']]);

        return ['message' => 'Phone number updated sucessfully'];
    }
}
