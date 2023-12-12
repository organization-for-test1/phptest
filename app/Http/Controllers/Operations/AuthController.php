<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    //operation login 
    public function operationLogin(Request $request){
        $validateData = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        if($validateData->fails()){
            return  response()->json(array(
                'message' => $validateData->errors()->all()
            ), 400);
        }
        $operation = User::where(['email' => $request->email, 'password' => $request->password])->first();
        if(!$operation){
            return  response()->json(array(
                'message' => 'Operationrole Not Found'), 404);
        }

        if($operation->roleId == 3){
            $token = rand(100000, 999999) . Carbon::now()->timestamp;
            $updateToken = DB::table('users')->where('userId', $operation->userId)->update([
                'token' => $token,
            ]);
            return response()->json([
                'message' => 'Operation logged in successfully',
                'token' => $token,
                'userId' => $operation->userId,
                'roleId' => $operation->roleId
            ], 200);
        } else {
            return response(["message" => "Invalid roleid"], 404);
        }
    }
}


