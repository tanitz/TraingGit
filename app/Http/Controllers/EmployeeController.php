<?php

namespace App\Http\Controllers;

use App\Http\Libraries\JWT\JWTUtils;
use DateTime;
use Dflydev\DotAccessData\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

Class EmployeeController extends Controller
{   
    private $jwtUtils;
    public function __construct()
    {
        $this -> jwtUtils = new JWTUtils();
    }

    function employeeSignIn(Request $request)
    {
        try {
            $rules = [
                "emp_id"    => ["required","numeric","digits:7"]
            ];

            $validator = Validator::make($request->all(),$rules);
            if($validator->fails())  return response()->json([
                "status"    => "error",
                "message"   => "Bad request",
                "data"      => [
                        [
                        "validator" => $validator->errors()
                        ]
                ],
            ],400);
            
            $empID = $request -> emp_id;
            // $result = DB::table("tb_users")->whereRaw("emp_id like '%$empID'") ->get();
            // $result = DB::table("tb_users")->whereRaw("where emp_id ilike '%2'",[$empID]) ->get();
            $result = DB::table("tb_employees")->selectRaw(
                "emp_id
                ,name->>'th' as name_th
                ,role"
            )->whereRaw("emp_id like '%$empID'")->get();

            if (\count($result) == 0) return response()->json([
                "status"    =>     "error",
                "message"   =>    "User does not exists",
                "data"      =>     [],
            ], 400);


            date_default_timezone_set('Asia/Bangkok');
            $now = new \DateTime();
            $payload = [
                "emp_id"    =>  $result[0]->emp_id,
                "role"      =>  $result[0]->role,
                "iat"       =>  $now->getTimestamp(),
                // "exp"       =>  $now->modify("+3 hours")->getTimestamp(),
                "exp"       =>  $now->modify("+24 hours")->getTimestamp(),
            ];

            $token = $this->jwtUtils->generateToken($payload);

            return response()->json([
                "status"    => "success",
                "message"   => "Sign in success",
                "data"      => [[
                    "emp_id"    => $result[0] -> emp_id,
                    "name"      => $result[0] -> name_th,
                    "role"      => $result[0] -> role,
                    "token"     => $token,
                ]],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }
}
