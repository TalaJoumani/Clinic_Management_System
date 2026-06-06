<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddDoctorRequest;
use Illuminate\Http\Request;
use App\Services\AdminServices;

class AdminController extends Controller
{
    protected AdminServices $adminServices;

    public function __construct(AdminServices $adminServices)
    {
        $this->adminServices = $adminServices;
    }

    public function addDoctor(AddDoctorRequest $addDoctorRequest){
        if(auth('sanctum')->user()->role !== 'admin'){
            return response()->json([
                'message' => 'This service is only for admins',
            ],401);
        }

        $doctor=$this->adminServices->addDoctor($addDoctorRequest->validated());
        return response()->json([
            'message' => 'Doctor added successfully',
            'doctor' => $doctor
        ],210);
    }

    public function deleteDoctor(Request $request){
       $request->validate([
            'doctorId' => 'required|integer',
        ]);
        if(auth('sanctum')->user()->role !== 'admin'){
            return response()->json([
                'message' => 'This service is only for admins',
            ],401);
        }
        $result = $this->adminServices->deleteDoctor($request->doctorId);
        return response()->json([
            'message' => 'Doctor deleted successfully',
        ],200);
    }
    

    public function getAllDoctors(){
        if(auth('sanctum')->user()->role !== 'admin'){
            return response()->json([
                'message' => 'This service is only for admins',
            ],401);
        }
        $doctors=$this->adminServices->getAllDoctors();
        return response()->json([
            'message' => 'Doctors retrieved successfully',
            'doctors' => $doctors
        ],200);
    }

    public function updateDoctor(Request $request){
        $validatedData = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email',
            'password' => 'sometimes|string|min:8|confirmed',
            'phone' => 'sometimes|string|max:20',
            'specialization' => 'sometimes|string|max:255',
            'day' => 'in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,All',
            'start_time' => 'date_format:H:i',
            'end_time' => 'date_format:H:i|after:start_time',
            'home_visit' => 'boolean',  
            'price' => 'sometimes|numeric|min:0',    
        ]);
        
        return $this->adminServices->updateDoctor($request->doctorId, $validatedData);
    }
}
