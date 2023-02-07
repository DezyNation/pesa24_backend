<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\v1\UserResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Eko\Agent\AgentManagementController;

class ProfileController extends AgentManagementController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
    public function info()
    {
        return new UserResource(User::findOrFail(Auth::id()));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {     
        // $data = collect($request->values);
        $request->validate([
            'values.firstName' => ['required', 'max:255'],
            'values.lastName' => ['required', 'string', 'max:255'],
            'values.dob' => ['required', 'date'],
            'values.aadhaar' => ['required', 'digits:12', 'integer', Rule::unique('users', 'aadhaar')->ignore(auth()->user()->id)],
            'values.line' => ['required', 'string', 'max:255'],
            'values.city' => ['required', 'string', 'max:255'],
            'values.pincode' => ['required','digits:6','integer'],
            'values.state' => ['required', 'string', 'max:255'],
            'values.phone' => ['required', Rule::unique('users', 'phone_number')->ignore(auth()->user()->id)],
            'values.pan' => ['required', 'regex:/^([A-Z]){5}([0-9]){4}([A-Z]){1}/', Rule::unique('users', 'pan_number')->ignore(auth()->user()->id)],
            'values.companyName' => ['required', 'max:255']
        ]);

        User::where('id', auth()->user()->id)->update([
            'first_name' => $request['values']['firstName'],
            'last_name' => $request['values']['lastName'],
            'dob' => $request['values']['dob'],
            'aadhaar' => $request['values']['aadhaar'],
            'line' => $request['values']['line'],
            'city' => $request['values']['city'],
            'pincode' => $request['values']['pincode'],
            'state' => $request['values']['state'],
            'phone_number' => $request['values']['phone'],
            'pan_number' => $request['values']['pan'],
            'company_name' => $request['values']['companyName'],
            'profile' => 1
        ]);

        if (is_null(auth()->user()->user_code))
           return  $this->userOnboard();


        return new UserResource(User::findOrFail(Auth::id()));
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
}
