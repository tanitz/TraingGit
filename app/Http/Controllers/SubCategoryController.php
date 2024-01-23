<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Libraries\JWT\JWTUtils;
use Faker\Extension\Extension;
use App\Http\Controllers\ServiceController;

class SubCategoryController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this -> jwtUtils = new JWTUtils();
    }

    function createSub(Request $request){
        try{
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if(!$jwt->state) return response()->json([
                "status"=>"error",
                "message"=>"Timeout",
                "data"=>[],
            ],401);
            
            $rules = [
                "main_category_id" => ["required","string"], 
                "sub_category_desc" => ["required","string","min:1"] //ต้องการ string อย่างน้อย 1 ตัวอักษร
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
            try{
                $result = DB::table("tb_main_service_categories")->selectRaw(
                "main_category_id")->where("main_category_id",$request->main_category_id)->get();
            }catch(\Exception $e){
                return response()->json([
                "status"    => "error",
                "message"   => "Not Found Main Id",
                "data"      => [
                ],
                ],401);
            }
            
            // sub_category_desc
            $result = DB::table("tb_sub_service_categories")->insert([
                    "main_category_id" => $request->main_category_id,
                    "sub_category_desc" => $request->sub_category_desc
                    ]);
            
            
            return response()->json([
                "status"    => "success",
                "message"   => "createSub category success",
                "data"      => [["result"=>$result]],
            ], 201);

        }catch(\Exception $e){
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }

    }
    
    function getAllSub(Request $request){
        try{
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if(!$jwt->state) return response()->json([
                "status"=>"error",
                "message"=>"Timeout",
                "data"=>[],
            ],401);
            
           

            // $result = DB::table("tb_main_service_categories")->select(
            //     ["main_category_id","main_category_desc","created_at"]
            // )->orderByDesc("created_at")->get();
            $result = DB::table("tb_sub_service_categories")->selectRaw(
                "main_category_id
                ,sub_category_id
                ,sub_category_desc
                ,created_at::varchar(19) as created_at
                ,updated_at::varchar(19) as updated_at"
            )->orderByDesc("created_at")->get();
            
            // $result = DB::table("tb_main_service_categories")->orderBy("created_at")->get();


            return response()->json([
                "status"    => "success",
                "message"   => "getAllSub category success",
                "data"      => $result,
            ], 201);

        }catch(\Exception $e){
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }

    }
    

    function updateSub(Request $request){
        try{
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if(!$jwt->state) return response()->json([
                "status"=>"error",
                "message"=>"Timeout",
                "data"=>[],
            ],401);
            
            $rules = [
                "sub_category_id" => ["required"], 
                "sub_category_desc" => ["required","string"] //ต้องการ string อย่างน้อย 1 ตัวอักษร
            ];
            $validator = Validator::make($request->all(),$rules);
            if($validator->fails())  return response()->json([
                "status"    => "error",
                "message"   => "Bad request1",
                "data"      => [
                        [
                        "validator" => $validator->errors()
                        ]
                ],
            ],400);
            try{
                $result = DB::table("tb_sub_service_categories")->selectRaw(
                "main_category_id")->where("sub_category_id",$request->sub_category_id)->get();
            }catch(\Exception $e){
                return response()->json([
                "status"    => "error",
                "message"   => "Not Found sub_category_id",
                "data"      => [
                ],
                ],401);
            }
            
            // sub_category_desc
            $result = DB::table("tb_sub_service_categories")->where("sub_category_id",$request->sub_category_id)->update([
                    "sub_category_id" => $request->sub_category_id,
                    "sub_category_desc" => $request->sub_category_desc,
                    "updated_at"        => DB::raw("now()"),
                    ]);
            
            

            return response()->json([
                "status"    => "success",
                "message"   => "updateSub category success",
                "data"      => [["result"=>$result]],
            ], 201);

        }catch(\Exception $e){
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }

    }
    
    function deleteSub(Request $request){
        try{
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if(!$jwt->state) return response()->json([
                "status"=>"error",
                "message"=>"Timeout",
                "data"=>[],
            ],401);
            
            $rules = [
                "sub_category_id" => ["required","uuid"] //! Added
            ];

            $validator = Validator::make($request->all(),$rules);
            if($validator->fails())  return response()->json([
                "status"    => "error",
                "message"   => "Bad request1",
                "data"      => [
                        [
                        "validator" => $validator->errors()
                        ]
                ],
            ],400);

            
            $result = DB::table("tb_services")->selectRaw(
                "sub_category_id")->where("sub_category_id",$request->sub_category_id)->take(1)->get();

            if (\count($result) > 0) return response()->json([
                "status"    =>     "error",
                "message"   =>    "Not",
                "data"      =>     [],
            ], 400);

            // $result2 = DB::table("tb_sub_service_categories")->where("sub_category_id",$request->sub_category_id)->delete();

            DB::table("tb_sub_service_categories")->where("sub_category_id",$request->sub_category_id)->delete();

            return response()->json([
                "status"    => "success",
                "message"   => "delete sup category success",
                "data"      => [["result"=>$result]],
            ], 201);

        }catch(\Exception $e){
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }

    }

}
