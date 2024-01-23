<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Http\Libraries\JWT\JWTUtils;

class TodoController extends Controller
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

            DB::table("tb_to_do_list")->insert([
                "title"           => $request->title,
                "status"          => $request->status,
                "start_date"      => $request->start_date,
                "end_date"        => $request->end_date,
                "details"         => $request->details,
                "color"           => $request->color,
                "tag"             => $request->tag,
                "updated_at"      => DB::raw("now()"),
                "creator_id"      => $decoded->emp_id
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Created to do list successfully",
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

    //* [GET] /todo-list
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

            $result = DB::table("tb_to_do_list")
                ->select()
                ->where("creator_id", '=', $decoded->emp_id)
                ->orderByDesc("start_date")
                ->get();


            return response()->json([
                "status" => "success",
                "message" => "Data from query",
                "data" => $result,
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