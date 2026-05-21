<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminServices
{
    public function addDoctor(array $data)
    {
        $user= User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => 'doctor',
            'birth'      => $data['birth']??null,
            'phone'      => $data['phone'],
            'is_verified' => true,
            'gender'     => $data['gender'],
         ]);
           $doctor= $user->doctor()->create([
                'specialization' => $data['specialization'],
                'home_visit'   => $data['home_visit'],
                'admin_id'     => auth('sanctum')->id(),
            ]);

            $doctor->schedules()->create([
                'day' => $data['day'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
            ]);

            return $user->load('doctor.schedules');
                                     
    }


    public function deleteDoctor($doctorId)
    {
        $doctor = Doctor::where('user_id', $doctorId)->where('admin_id', auth('sanctum')->id())->firstOrFail();
        if (!$doctor) {    
    return response()->json([
        'message' => 'Doctor not found ',
    ], 404);
    }
    if($doctor->admin_id !== auth('sanctum')->id()){
        return response()->json([
            'message' => 'You are not authorized to delete this doctor',
        ], 403);
    }
    $userId=$doctor->user_id;
   
        $user=User::find($userId);
        if(!$user){
        return response()->json([
            'message' => 'Doctor not found ',
        ], 200);
    }
     $doctor->schedules()->delete();
    $doctor->delete();
     $user->delete();
     return response()->json([
        'message' => 'Doctor deleted successfully',
    ], 200);
    }


    public function getAllDoctors()
    {
        $doctors = Doctor::where('admin_id', auth('sanctum')->id())->with('user')->get();
        return response()->json([
            'doctors' => $doctors,
        ], 200);
    }

    public function updateDoctor($doctorId, array $data)
    {
        $doctor = Doctor::where('user_id', $doctorId)->where('admin_id', auth('sanctum')->id())->first();
        if (!$doctor) {    
            return response()->json([
                'message' => 'Doctor not found ',
            ], 404);
        }
        if($doctor->admin_id !== auth('sanctum')->id()){
            return response()->json([
                'message' => 'You are not authorized to update this doctor',
            ], 403);
        }
        $doctor->update([
            'specialization' => $data['specialization'] ?? $doctor->specialization,
            'day' => $data['day'] ?? $doctor->day,
            'start_time'   => $data['start_time'] ?? $doctor->start_time,
            'end_time'     => $data['end_time'] ?? $doctor->end_time,
            'home_visit'   => $data['home_visit'] ?? $doctor->home_visit,
        ]);

        if(isset($data['first_name']) || isset($data['last_name']) || isset($data['email']) || isset($data['phone']) || isset($data['password']) ){
            $doctor->user()->update([
                'first_name' => $data['first_name'] ?? $doctor->user->first_name,
                'last_name'  => $data['last_name'] ?? $doctor->user->last_name,
                'email'      => $data['email'] ?? $doctor->user->email,
                'phone'      => $data['phone'] ?? $doctor->user->phone,
                'password'   => isset($data['password']) ? Hash::make($data['password']) : $doctor->user->password,
            ]);
        }

        return response()->json([
            'message' => 'Doctor updated successfully',
            'doctor' => $doctor->load('user'),
        ], 200);
    }
}