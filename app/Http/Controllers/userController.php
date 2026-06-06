<?php

namespace App\Http\Controllers;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Services\UserServices;

class userController extends Controller
{
    protected $userServices;

    public function __construct(UserServices $userServices)
    {
        $this->userServices = $userServices;
    }

    public function getMyProfile()
    {
        $result = $this->userServices->getMyProfile();
        return response()->json([
            'data' => $result,
        ]);
    }
}
