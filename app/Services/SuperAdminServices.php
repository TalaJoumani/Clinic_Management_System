<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminServices
{
    public function addAdmin(array $data)
    {
        return User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => 'admin',
            'birth'      => $data['birth']??null,
            'phone'      => $data['phone'],
            'gender'     => $data['gender'],
            'is_verified' => true,
        ]);
    }



    public function deleteAdmin($id)
    {
        $admin = User::where('id', $id)->where('role', 'admin')->firstOrFail();
        return $admin->delete();
    }


    public function getAllAdmins()
    {
        return User::where('role', 'admin')->get();
    }

    public function getUsersByRole(string $role){
        if(auth('sanctum')->user()->role !== 'super_admin') {
            return [
                'message'=>'Unauthorized,only super admin can access this resource'
            ];
        }
        $result=User::where('role',$role)->with(['doctor'])->get();

        return $result;
    }
}