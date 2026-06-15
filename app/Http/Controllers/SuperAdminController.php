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
        ],210);
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
}
