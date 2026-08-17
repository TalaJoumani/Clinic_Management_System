<?php

namespace App\Services;

use App\Mail\AdminWelcome;
use App\Models\Appointment;
use App\Models\Doctor;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Facades\Log;
use App\Models\Inventory_logs;
use App\Models\Item;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SuperAdminServices
{
    public function addAdmin(array $data)
    {
        $admin = User::create([
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
            Mail::to($admin->email)->send(new AdminWelcome($admin, $data['password']));
        return $admin;

    }



    public function deleteAdmin(int $id)
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
        if(!in_array($role, ['admin', 'doctor'])) {
            return [
                'message'=>'Invalid role, only doctor and admin roles are allowed',
            ];
        }
         $result=User::where('role',$role)->get();
        $result=User::where('role',$role)->with(['doctor'])->get();

        return $result;
    }

    public function createItem(array $data)
    {
         if(auth('sanctum')->user()->role !== 'super_admin') {
            return [
                'message'=>'Unauthorized,only super admin can access this resource'
            ];
        }
        return DB::transaction(function ()use ($data){
            $item=Item::create([
                'name'=>$data['name'],
                'quantity'=>$data['quantity'],
                'min_quantity'=>$data['min_quantity'],
                'category'=>$data['category'],
            ]);
            Inventory_logs::create([
                'item_id'=>$item->id,
                'type'=>'addition',
                'quantity_changed'=>$data['quantity'],
            ]);
            return $item;
        });
        }


        public function addItem(int $itemId, int $quantityToAdd)
        {
            return DB::transaction(function () use ($itemId, $quantityToAdd) {
                $item = Item::find($itemId);
                if (!$item) {
                    return [
                        'message' => 'Item not found',
                    ];
                }
                $item->increment('quantity', $quantityToAdd);
                Inventory_logs::create([
                    'item_id' => $item->id,
                    'type' => 'addition',
                    'quantity_changed' => $quantityToAdd,
                ]);
                $this-> sendAddNotification($item,$quantityToAdd);
                return $item->fresh();
            });
        }

        public function sendAddNotification($item,$addedQuantity){
            try{
                 $admin = User::where('role', 'admin')->first();

        if ($admin && $admin->token) {
            $messaging = app('firebase.messaging');

            $message = CloudMessage::fromArray([
                'token' => $admin->token,
                'notification' => [
                    'title' => 'Item Restocked 📦',
                    'body' => "The Super Admin has added {$addedQuantity} to '{$item->name}'. New quantity: {$item->quantity}",
                ],
                'data' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'notification_type' => 'item_restocked',
                    'item_id' => (string) $item->id,
                ],
                'android' => [
                    'notification' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'importance'  => 'HIGH',
                    ]
                ]
            ]);

            $messaging->send($message);
        }
    } catch (\Exception $e) {
        Log::error('Failed to send restock notification: ' . $e->getMessage());
    }
}

public function filter($filters)
{
    if (auth('sanctum')->user()->role !== 'super_admin') {
        return [
            'message' => 'Unauthorized, only super admin can access this resource'
        ];
    }

    $query = Appointment::query()->with('patient', 'location');

    $query->when(!empty($filters['start_date_filter']) && !empty($filters['end_date_filter']), function ($q) use ($filters) {
        $q->whereBetween('appointment_time', [$filters['start_date_filter'], $filters['end_date_filter']]);
    });

    $query->when(!empty($filters['type']), function ($q) use ($filters) {
        $q->where('type', $filters['type']);
    });
$query->when(!empty($filters['doctor_id']), function ($q) use ($filters) {
    $q->where('doctor_id', $filters['doctor_id']); //بياخد ال user_id
});
    

    return $query->get()->map(function ($appointment) {
        // نفس منطق addBooking: appointment->doctor_id مخزن فيه user_id
        $doctor = Doctor::where('user_id', $appointment->doctor_id)->with('user')->first();

        return [
            'id' => $appointment->id,
            'status' => $appointment->status,
            'type' => $appointment->type,
            'appointment_time' => $appointment->appointment_time,
            'doctor_name' => ($doctor && $doctor->user)
                ? $doctor->user->first_name . ' ' . $doctor->user->last_name
                : null,
            'doctor_specialization' => $doctor?->specialization,
        ];
    });
}

public function getMonthlyFinancialReport()
    {
        return Payment::query()
            ->select(
                DB::raw("DATE_FORMAT(payments.created_at, '%Y-%m') as financial_month"),
                DB::raw("SUM(CASE WHEN payments.status = 'partially_paid' THEN 1 ELSE 0 END) as partially_paid_count"),
                DB::raw("SUM(CASE WHEN payments.status = 'partially_paid' THEN payments.amount_paid ELSE 0 END) as partially_paid_total_amount"),
                DB::raw("SUM(CASE WHEN payments.status = 'fully_paid' THEN 1 ELSE 0 END) as fully_paid_count"),
                DB::raw("SUM(CASE WHEN payments.status = 'fully_paid' THEN payments.total_amount ELSE 0 END) as fully_paid_total_amount")
            )
            ->whereIn('payments.status', ['partially_paid', 'fully_paid'])
            ->groupBy('financial_month')
            ->orderBy('financial_month', 'DESC')
            ->get();
    }

    public function getPeakTimesAndTopDoctor()
    {
            $topDoctor = Appointment::query()
    ->join('users', 'appointments.doctor_id', '=', 'users.id')
    ->select(
        'users.id as doctor_id',
        DB::raw("CONCAT(users.first_name, ' ', users.last_name) as doctor_name"), 
        DB::raw('COUNT(appointments.id) as total_appointments')
    )
    ->groupBy('users.id', 'users.first_name', 'users.last_name') 
    ->orderBy('total_appointments', 'DESC')
    ->first();

        $peakHour = Appointment::query()
            ->select(
                DB::raw("HOUR(appointment_time) as hour"),
                DB::raw("COUNT(*) as appointment_count")
            )
            ->groupBy('hour')
            ->orderBy('appointment_count', 'DESC')
            ->first(); 

        $peakDay = Appointment::query()
            ->select(
                DB::raw("DAYNAME(appointment_time) as day_name"),
                DB::raw("COUNT(*) as appointment_count")
            )
            ->groupBy('day_name')
            ->orderBy('appointment_count', 'DESC')
            ->first(); 

        return [
            'top_doctor' => $topDoctor,
            'peak_hour' => $peakHour,
            'peak_day' => $peakDay,
        ];
    }


}

            