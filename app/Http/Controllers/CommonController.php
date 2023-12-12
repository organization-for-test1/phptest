<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommonController extends Controller
{
    //checks toke
    public static function checkToken($token, $roleId)
    {
        // Check if the token is null or empty
        if ($token == null || $token == '') {
            // Return response with empty array 
            return [];
        }

        // Find a user with the given token
        $token = DB::table('users')->where('token', $token)->whereIn('roleId', $roleId)->first();

        // Check if the token is invalid
        if (!$token) {
             // Return response with empty array 
             return [];
        } else {
           // Return response with user data
              return $token;
        }
    }

}
