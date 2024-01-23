<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

use App\Http\Libraries\JWT\JWTUtils;

class ServiceController extends Controller
{
    private $jwtUtils;
    public function __construct()
    {
        $this->jwtUtils = new JWTUtils();
    }

    private function randomName(int $length = 5)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return \implode($pass); //turn the array into a string
    }

    //TODO [POST] /service
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

            //! url_list = {service_name:{en: string, th: string}, url: string}[]
            $rules = [
                "main_category_id"  => ["required", "uuid"],
                "sub_category_id"   => ["nullable", "uuid"],
                "service_name.en"   => ["required", "string", "min:3"],
                "service_name.th"   => ["required", "string", "min:3"],
                "icon"              => ["nullable", "string"],
                "is_url_list"       => ["required", "boolean"],
                "url"               => ["required_if:is_url_list,false", "nullable", "string", "min:5"],
                "url_list"          => ["required_if:is_url_list,true", "present", "array"],
                "url_list.*.url"    => ["required_if:is_url_list,true", "nullable", "string", "min:3"],
                "url_list.*.service_name.en" => ["required_if:is_url_list,true", "nullable", "string", "min:3"],
                "url_list.*.service_name.th" => ["required_if:is_url_list,true", "nullable", "string", "min:3"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            // return response()->json(["request" => $request->all()]);

            //* Create folder
            $path = getcwd() . "\\..\\..\\images\\sevices\\";
            if (!is_dir($path)) mkdir($path, 0777, true);

            //* Create folder
            $folderPath = $path . $request->main_category_id . "\\";
            if (!is_dir($folderPath)) mkdir($folderPath, 0777, true);

            $fileName = $this->randomName(5) . time() . ".png";

            file_put_contents($folderPath . $fileName, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->icon))); //! Write image file

            DB::table("tb_services")->insert([
                "main_category_id"  => $request->main_category_id,
                "sub_category_id"   => $request->sub_category_id,
                "service_name"      => json_encode($request->service_name, JSON_UNESCAPED_UNICODE),
                "icon"              => $fileName,
                "is_url_list"       => $request->is_url_list,
                "url"               => $request->url,
                "url_list"          => is_null($request->url_list) || count($request->url_list) == 0 ? null : json_encode($request->url_list, JSON_UNESCAPED_UNICODE),
                "creator_id"        => $decoded->emp_id
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Created service successfully",
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

    //* [GET] /service
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

            $cacheKey = "/snc-one-way-api/service/get-all-services";
            $cacheData = Cache::get($cacheKey);
            if (!is_null($cacheData)) return response()->json([
                "status" => "success",
                "message" => "Data from cached",
                "data" => json_decode($cacheData),
            ]);

            $result = DB::table("tb_services as t1")->selectRaw(
                "t1.service_id
                ,t1.main_category_id,t2.main_category_desc
                ,t1.sub_category_id,t3.sub_category_desc
                ,t1.service_name
                ,t1.is_url_list
                ,t1.url
                ,t1.icon
                ,t1.url_list
                ,t1.is_disabled
                ,t1.created_at::varchar(19) as created_at
                ,t1.service_name->>'en' as service_name_en
                ,t1.service_name->>'th' as service_name_th
                ,t2.created_at as main_created_at
                ,t3.created_at as sub_created_at"
            )
                ->join("tb_main_service_categories as t2", "t1.main_category_id", "=", "t2.main_category_id")
                ->leftJoin("tb_sub_service_categories as t3", "t1.sub_category_id", "=", "t3.sub_category_id")
                ->orderByRaw("main_created_at,sub_created_at,service_name_en,service_name_th")->get();

            foreach ($result as $row) {
                $row->service_name = json_decode($row->service_name);
                // if (!is_null($row->icon)) $row->icon = "http://localhost:8081/training/2024/01/001/images/sevices/" . $row->main_category_id . "/" . $row->icon;
                if (!is_null($row->icon)) $row->icon = "https://snc-services.sncformer.com/dev/snc-one-way/images/sevices/" . $row->main_category_id . "/" . $row->icon;
                if (!is_null($row->url_list)) $row->url_list = json_decode($row->url_list);
                unset($row->service_name_en);
                unset($row->service_name_th);
                unset($row->main_created_at);
                unset($row->sub_created_at);
            }

            // Cache::put($cacheKey, json_encode($result, JSON_UNESCAPED_UNICODE), \DateInterval::createFromDateString('1 minutes'));
            Cache::put($cacheKey, json_encode($result, JSON_UNESCAPED_UNICODE), \DateInterval::createFromDateString('5 seconds'));

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

    //? [PUT] /service
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
            // $decoded = $jwt->decoded;

            //! url_list = {service_name:{en: string, th: string}, url: string}[]
            $rules = [
                "service_id"        => ["required", "uuid"],
                "main_category_id"  => ["required", "uuid"],
                "sub_category_id"   => ["nullable", "uuid"],
                "service_name.en"   => ["required", "string", "min:3"],
                "service_name.th"   => ["required", "string", "min:3"],
                "icon"              => ["nullable", "string"],
                "is_url_list"       => ["required", "boolean"],
                "url"               => ["required_if:is_url_list,false", "nullable", "string", "min:5"],
                "url_list"          => ["required_if:is_url_list,true", "present", "array"],
                "url_list.*.url"    => ["required_if:is_url_list,true", "nullable", "string", "min:3"],
                "url_list.*.service_name.en" => ["required_if:is_url_list,true", "nullable", "string", "min:3"],
                "url_list.*.service_name.th" => ["required_if:is_url_list,true", "nullable", "string", "min:3"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            // return response()->json(["request" => $request->all()]);
            $data = [
                "main_category_id"  => $request->main_category_id,
                "sub_category_id"   => $request->sub_category_id,
                "service_name"      => json_encode($request->service_name, JSON_UNESCAPED_UNICODE),
                "is_url_list"       => $request->is_url_list,
                "url"               => $request->url,
                "url_list"          => is_null($request->url_list) || count($request->url_list) == 0 ? null : json_encode($request->url_list, JSON_UNESCAPED_UNICODE),
                "updated_at"        => DB::raw("now()"),
            ];

            // if (!is_null($request->icon)) $data["icon"] = $request->icon;

            if (!is_null($request->icon)) {
                //* Create Folder
                $path = getcwd() . "\\..\\..\\images\\sevices\\";
                if (!is_dir($path)) mkdir($path, 0777, true);

                //* Create folder
                $folderPath = $path . $request->main_category_id . "\\";
                if (!is_dir($folderPath)) mkdir($folderPath, 0777, true);

                //! Delete old file
                $checkFile = DB::table("tb_services")->select(["icon"])->where("service_id", $request->service_id)->whereRaw("icon is not null")->get();
                if (count($checkFile) !== 0) {
                    $oldFilePath = $folderPath . $checkFile[0]->icon;
                    if (file_exists($oldFilePath)) unlink($oldFilePath);
                }

                $newFileName = $this->randomName(5) . time() . ".png";
                //* Write file
                file_put_contents($folderPath . $newFileName, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $request->icon)));

                $data["icon"] = $newFileName;
            }

            $result = DB::table("tb_services")->where("service_id", $request->service_id)->update($data);

            if ($result == 0) return response()->json([
                "status" => "error",
                "message" => "service_id does not exists",
                "data" => [],
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Updated service successfully",
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

    //! [DELETE] /service
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

            $rules = [
                "service_id"      => ["required", "uuid"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            $result = DB::table("tb_services")->where("service_id", $request->service_id)->delete();

            if ($result == 0) return response()->json([
                "status" => "error",
                "message" => "service_id does not exists",
                "data" => [],
            ]);

            return response()->json([
                "status" => "success",
                "message" => "Deleted service successfully",
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

    //? [PATCH] /service/enable-disable
    function enableAndDisable(Request $request)
    {
        try {
            $authorize = $request->header("Authorization");
            $jwt = $this->jwtUtils->verifyToken($authorize);
            if (!$jwt->state) return response()->json([
                "status" => "error",
                "message" => "Unauthorized",
                "data" => []
            ], 401);

            $rules = [
                "service_id"      => ["required", "uuid"],
                "is_disabled"      => ["required", "boolean"],
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) return response()->json([
                "status" => "error",
                "message" => "Bad request",
                "data" => [
                    ["validator" => $validator->errors()]
                ]
            ], 400);

            // return response()->json(["type" => gettype($request->is_disabled)]);

            $result = DB::table("tb_services")->where("service_id", $request->service_id)->update([
                "is_disabled" => $request->is_disabled,
                "updated_at" => DB::raw("now()"),
            ]);

            if ($result == 0) return response()->json([
                "status" => "error",
                "message" => "service_id does not exists",
                "data" => [],
            ]);

            return response()->json([
                "status" => "success",
                "message" => ($request->is_disabled ? "Disabled" : "Enabled") . " service successfully",
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
}