<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddAdminRequest;
use Illuminate\Http\Request;
use App\Services\SuperAdminServices;

class SuperAdminController extends Controller
{
    protected SuperAdminServices $superAdminServices;

    public function __construct(SuperAdminServices $superAdminServices)
    {
        $this->superAdminServices = $superAdminServices;
    }


    // Add a new admin
    public function addAdmin(AddAdminRequest $addAdminRequest){
        if(auth('sanctum')->user()->role !== 'super_admin'){
            return response()->json([
                'message' => 'Unauthorized'
            ],401);
        }
        $admin=$this->superAdminServices->addAdmin($addAdminRequest->validated());
        return response()->json([
            'message' => 'Admin added successfully',
            'admin' => $admin
        ],201);
    }

 
    // Delete an admin
    public function deleteAdmin(Request $request){
        if(auth('sanctum')->user()->role !== 'super_admin'){
            return response()->json([
                'message' => 'Unauthorized'
            ],401);
        }
        $this->superAdminServices->deleteAdmin($request->id);
        return response()->json([
            'message' => 'Admin deleted successfully',
        ],200);
    }


    // Get all admins
    public function getAllAdmins(){
        if(auth('sanctum')->user()->role !== 'super_admin'){
            return response()->json([
                'message' => 'Unauthorized'
            ],401);
        }
        $admins=$this->superAdminServices->getAllAdmins();
        return response()->json([
            'message' => 'Admins retrieved successfully',
            'admins' => $admins
        ],200);
    }

    public function getUsersByRole(Request $request){
        $role=$request->query('role');
        if(!$role) {
            return response()->json([
                'message'=>'Role query parameter is required'
            ],400);
        }
        $result=$this->superAdminServices->getUsersByRole($role);
        return response()->json([
            'data'=>$result,
        ],200);
    }

    public function createItem(Request $request){
        $data=$request->validate([
            'name'=>'required|string|max:255',
            'quantity'=>'required|integer|min:0',
            'min_quantity'=>'required|integer|min:0',
            'category'=>'required|string|max:255',
        ]);
        $item=$this->superAdminServices->createItem($data);
        return response()->json([
            'message'=>'Item created successfully',
            'item'=>$item,
        ],201);
    }

    public function addItem(Request $request,int $id){
         if(auth('sanctum')->user()->role !== 'super_admin'){
            return response()->json([
                'message' => 'Unauthorized'
            ],401);
        }
        $request->validate([
            'quantity'=>'required|integer|min:1'
        ]);
        $result=$this->superAdminServices->addItem($id,$request->quantity);
        return response()->json([
            'message'=>'item quantity increased',
            'item'=>$result,
        ]);
    }

    public function filter(Request $request){
        $request->validate([
            'start_date_filter'=>'nullable|date',
            'end_date_filter'=>'nullable|date|after_or_equal:start_date_filter',
             'type' => 'nullable|string|in:clinic,online,home',
            'doctor_id' => 'nullable|exists:doctors,user_id',
        ]);
        $result=$this->superAdminServices->filter($request->all());
        return response()->json([
            'message'=>$result,
        ]);
    }

    public function getMonthlyFinancialReport()
    {
        $report = $this->superAdminServices->getMonthlyFinancialReport();

        return response()->json([
            'status' => 'success',
            'message' => 'Monthly financial report retrieved successfully by month',
            'data' => $report
        ], 200);
    }


    public function getPeakTimesAndTopDoctor()
    {
        $analytics = $this->superAdminServices->getPeakTimesAndTopDoctor();

        return response()->json([
            'status' => 'success',
            'message' => 'Analytics report retrieved successfully',
            'data' => $analytics
        ], 200);
    }

    public function getAllDoctors(){
        $result=$this->superAdminServices->getAllDoctors();
        return response()->json([
            'message'=>$result,
        ]);
    }

    public function getAppointmentsCount(){
        $result=$this->superAdminServices->getAppointmentsCount();
        return response()->json([
            'message'=>$result,
        ]);
    }

    public function getAllItems(){
        $result=$this->superAdminServices->getAllItems();
        return response()->json([
            'message'=>$result,
        ]);
    }

}

