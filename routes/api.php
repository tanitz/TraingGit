<?php

// use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\MainCategoryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\RoleController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// XML, JSON, Protobuff
// method -> GET,POST,PUT,PATCH,DELETE 


// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/',function () {
    return response()->json([
        "status" => "success",
        "message" => "welcome to SNC One Way API",
        "data" => [],
    ]);
});

Route::get('/hello',function () {
    return response()->json([
        "status" => "success",
        "message" => "world",
        "data" => [],
    ]);
});


Route::prefix("employee")->controller(EmployeeController::class)->group(function () {
    Route::post("/sign-in", "employeeSignIn"); 
    // link , function
});


Route::prefix("main-category")->controller(MainCategoryController::class)->group(function () {
    Route::post("/", "create");
    Route::get("/", "getAll");
    Route::put("/", "update");
    Route::delete("/", "delete");
});

Route::prefix("sub-category")->controller(SubCategoryController::class)->group(function () {
    Route::post("/", "createSub");
    Route::get("/", "getAllSub");
    Route::put("/", "updateSub");
    Route::delete("/", "deleteSub");
});

Route::prefix("service")->controller(ServiceController::class)->group(function () {
    Route::post("/", "create");
    Route::get("/", "getAll");
    Route::put("/", "update");
    Route::delete("/", "delete");
    Route::patch("/enable-disable", "enableAndDisable");
});

Route::prefix("event")->controller(EventController::class)->group(function () {
    Route::get("/categories", "eventCategories");
    Route::post("/", "create");
    Route::get("/pending-approvals", "pendingApprovals");
    Route::get("/events", "events");
    Route::get("/event-count", "eventCount");
    Route::put("/", "update");
    Route::delete("/", "delete");
    Route::patch("/approve", "approve");
    Route::patch("/disapprove", "disapprove");
});

Route::prefix("todolist")->controller(TodoController::class)->group(function () {
    Route::post("/create", "create");
    Route::get("/", "getAll");
    Route::put("/update", "update");
    Route::delete("/delete", "delete");
});

Route::prefix("role-setting")->controller(RoleController::class)->group(function () {
    Route::post("/create", "create");
    Route::get("/", "getAll");
    Route::put("/update", "update");
    Route::delete("/delete", "delete");
});

// Route::get('/pg-connect',function () {
//     try{
//         $result = DB::table("tb_users")->get();
//         return response()->json(["result"=>$result]);
//     }catch(\Exception $e){
//         return response()->json(["error"=>$e->getMessage()],500);
//     }
// });

