<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Http\Libraries\JWT\JWTUtils;


class RoleController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

    //TODO [POST] /todo-list
    function create(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => []
            ], 401);
            $decoded = $jwt->decoded;

            $rules = [
                "role"      => ["required", "string"],
                "functions"     => ["required", "array"],
                "service_id" => ["required", "array"],
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) return response()->json([
                "status" => "error1",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);
         
            // $my_json_var = json_decode($request->functions);

            DB::table("tb_access_control")->insert([
                "role"           => $request->role,
                "functions"      => json_encode( $request->functions),
                "service_id"     => json_encode($request->service_id),
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Created to do list successfully",
                "data" => [],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error2",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

    //* [GET] /role-list
    function getAll(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => []
            ], 401);
            $decoded = $jwt->decoded;
            $cacheKey = "/snc-one-way-api/service/get-all-services";
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData)) return response()->json([
                "status" => "success",
                "message" => "Data from cached",
                "data" => json_decode($cacheData),
            ]);

            $empID = $decoded->emp_id;
            $user = DB::table("tb_employees as t1")->selectRaw(
                "t1.emp_id
                ,t1.name->>'th' AS Name
                ,t1.role
                ,t2.service_id
                ,jsonb_array_elements(service_id)->>'id' AS service_id 
                ,jsonb_array_elements(service_id)->>'is_checked' AS service_disable
                ,jsonb_array_elements(functions)->>'id' AS functions_id 
                ,jsonb_array_elements(functions)->>'is_checked' AS functions_disable
                ,t
                "
            )->whereRaw("t1.emp_id like '%$empID'")
            ->join("tb_access_control as t2", "t2.role", "=", "t1.role")->get();

            return response()->json([
                "status" => "success",
                "message" => "Data from query",
                "data" => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

    //? [PUT] /to-do list
    function update(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => []
            ], 401);
            $decoded = $jwt->decoded;

            $rules = [
                "list_id"    => ["required", "uuid"],
                "title"      => ["required", "string"],
                "status"     => ["required", "string"],
                "start_date" => ["required", "date"],
                "end_date"   => ["required", "date"],
                "details"    => ["required", "string"],
                "color"      => ["required", "string"],
                "tag"        => ["required", "string"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            $result = DB::table("tb_to_do_list")->where("creator_id", $decoded->emp_id)->where("list_id", $request->list_id)->update([
                "title"           => $request->title,
                "status"          => $request->status,
                "start_date"      => $request->start_date,
                "end_date"        => $request->end_date,
                "details"         => $request->details,
                "color"           => $request->color,
                "tag"             => $request->tag,
                "updated_at"      => DB::raw("now()"),
            ]);

            if ($result == 0) return response()->json([
                "status" => "error",
                "message" => "to_do_list does not exists",
                "data" => [],
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Updated to do list successfully",
                "data" => [],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }

    //! [DELETE] /todo-list
    function delete(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => []
            ], 401);
            $decoded = $jwt->decoded;

            $rules = [
                "list_id"      => ["required", "uuid"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);


            $result = DB::table("tb_to_do_list")->where("creator_id", $decoded->emp_id)->where("list_id", $request->list_id)->delete();

            if ($result == 0) return response()->json([
                "status" => "error",
                "message" => "to_do_list does not exists",
                "data" => [],
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Deleted to do list successfully",
                "data" => [["result" => $result]],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }
    }
}