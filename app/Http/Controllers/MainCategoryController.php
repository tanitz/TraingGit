<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Libraries\JWT\JWTUtils;

class MainCategoryController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this -> jwtUtils = new JWTUtils();
    }

    function create(Request $request){
        try{
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if(!$jwt->state) return response()->json([
                "status"=>"error",
                "message"=>"Timeout",
                "data"=>[],
            ],401);
            
            $rules = [
                "main_category_desc" => ["required","string","min:1"] //ต้องการ string อย่างน้อย 1 ตัวอักษร
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

            DB::table("tb_main_service_categories")->insert([
                "main_category_desc" => $request->main_category_desc
            ]);


            return response()->json([
                "status"    => "success",
                "message"   => "Create main category success",
                "data"      => [],
            ], 201);

        }catch(\Exception $e){
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }

    }
    
    function getAll(Request $request){
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
            $result = DB::table("tb_main_service_categories")->selectRaw(
                "main_category_id
                ,main_category_desc
                ,created_at::varchar(19) as created_at
                ,updated_at::varchar(19) as updated_at"
            )->orderByDesc("created_at")->get();
            
            // $result = DB::table("tb_main_service_categories")->orderBy("created_at")->get();


            return response()->json([
                "status"    => "success",
                "message"   => "getAll main category success",
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
    
    function update(Request $request){
        try{
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if(!$jwt->state) return response()->json([
                "status"=>"error",
                "message"=>"Timeout",
                "data"=>[],
            ],401);
            
            $rules = [
                "main_category_id" => ["required","uuid"] //! Added
                ,"main_category_desc" => ["required","string","min:1"] //ต้องการ string อย่างน้อย 1 ตัวอักษร
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

            $result=DB::table("tb_main_service_categories")->where("main_category_id",$request->main_category_id)->update([
                "main_category_desc"    => $request->main_category_desc,
                "updated_at"            => DB::raw("now()"),
            ]);

            if($result===0){
                return response()->json([
                    "status"    => "fail",
                    "message"   => "update main category fail",
                    "data"      => [["result" => $result]],
                ], 201);
            }


            return response()->json([
                "status"    => "success",
                "message"   => "update main category success",
                "data"      => [["result" => $result]],
            ], 201);

        }catch(\Exception $e){
            return response()->json([
                "status"    => "error",
                "message"   => $e->getMessage(),
                "data"      => [],
            ], 500);
        }

    }

    function delete(Request $request){
        try{
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if(!$jwt->state) return response()->json([
                "status"=>"error",
                "message"=>"Timeout",
                "data"=>[],
            ],401);
            
            $rules = [
                "main_category_id" => ["required","uuid"] //! Added
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

            DB::table("tb_main_service_categories")->where("main_category_id",$request->main_category_id)->delete();


            return response()->json([
                "status"    => "success",
                "message"   => "delete main category success",
                "data"      => [],
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
