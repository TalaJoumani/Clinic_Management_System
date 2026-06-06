<?php
namespace App\Services; 
use App\Models\User;
use Illuminate\Support\Facades\Auth;
class UserServices
{
    public function getMyProfile()
    {
        /** @var \App\Models\User $user */
    $user=auth('sanctum')->user();

        if ($user->role === 'doctor') {
            return $user->load('doctor');
        }else if ($user->role === 'patient') {
            return $user->load('patient');
        }

        return $user;
    }


    public function updateUser($id, $data)
    {
        $user = User::find($id);
        if ($user) {
            $user->update($data);
            return $user;
        }
        return null;
    }

    public function deleteUser($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->delete();
            return true;
        }
        return false;
    }
}