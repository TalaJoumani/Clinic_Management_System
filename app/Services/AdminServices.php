<?php

namespace App\Services;
use App\Mail\DoctorWelcome;
use App\Models\Doctor;
use App\Models\User;
use App\Image\ImageUpload;
use App\Models\Inventory_logs;
use App\Models\Notification;
use App\Models\Offer;
use App\Models\Item;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;

class AdminServices
{
     protected ImageUpload $imageUpload;
   public function __construct(ImageUpload $imageUpload){
    $this->imageUpload=$imageUpload;
   }
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
         $imagePath=null;
        if(isset($data['profile_photo'])&& $data['profile_photo']->isValid()){
            $imagePath=$this->imageUpload->upload($data['profile_photo'],'doctor_photo');
        }
           $doctor= $user->doctor()->create([
                'specialization' => $data['specialization'],
                'home_visit'   => $data['home_visit'],
                'admin_id'     => auth('sanctum')->id(),
                'profile_photo'=>$imagePath,
            ]);

            $doctor->schedules()->create([
                'day' => $data['day'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'price' => $data['price'] ,
            ]);
            Mail::to($user->email)->send(new DoctorWelcome($user, $data['password']));

            $user= $user->load('doctor.schedules');
            if($user->doctor && $user->doctor->profile_photo){
                $user->doctor->profile_photo=asset('storage/'.$user->doctor->profile_photo);
            }
            return $user;
    }


    public function deleteDoctor(int $doctorId)
    {
        $doctor = Doctor::where('user_id', $doctorId)->where('admin_id', auth('sanctum')->id())->first();
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
    if($doctor->profile_photo){
        $this->imageUpload->delete($doctor->profile_photo);
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
        $doctors = Doctor::where('admin_id', auth('sanctum')->id())->with(['user','schedules'])->get();
        $doctors->each(function($doctor){
            if($doctor->profile_photo){
                $doctor->profile_photo=asset('storage/'.$doctor->profile_photo);
            }
        });
        return response()->json([
            'doctors' => $doctors,
        ], 200);
    }

    public function updateDoctor(int $doctorId, array $data)
    {
        $user= auth('sanctum')->id();
        $doctor = Doctor::where('id', $doctorId)->where('admin_id', $user)->first();
        if (!$doctor) {    
            return response()->json([
                'message' => 'Doctor not found ',
            ], 404);
        }
        if($doctor->admin_id !== $user){
            return response()->json([
                'message' => 'You are not authorized to update this doctor',
            ], 403);
        }
        $imagePath=$doctor->profile_photo;
        if(isset($data['profile_photo'])&& $data['profile_photo']->isVaild()){
            if($doctor->profile_photo){
            $this->imageUpload->delete($doctor->profile_photo);
        }
        $imagePath=$this->imageUpload->upload($data['profile_photo'],'doctor_photo');
        }

        $doctor->update([
            'specialization' => $data['specialization'] ?? $doctor->specialization,
            'home_visit'   => $data['home_visit'] ?? $doctor->home_visit,
            'profile_photo'=>$imagePath,
        ]);

               if($doctor->user){
                $emailChanged=isset($data['email'])&& $data['email']!==$doctor->user->email;
                $passwordChanged=isset($data['password'])&& !empty($data['password']);
                 $doctor->user->update([
                   'first_name' => $data['first_name'] ?? $doctor->user->first_name,
                   'last_name'  => $data['last_name'] ?? $doctor->user->last_name,
                    'email'      => $data['email'] ?? $doctor->user->email,
                    'phone'      => $data['phone'] ?? $doctor->user->phone,
                    'password'   => isset($data['password']) ? Hash::make($data['password']) : $doctor->user->password,
                         ]);
                         if($emailChanged || $passwordChanged){
                            Mail::to($doctor->user->email)->send(new DoctorWelcome($doctor->user,$data['password']));
                         }
                              }

                              

            if(isset($data['day']) || isset($data['start_time']) || isset($data['end_time']) || isset($data['price'])){
                $schdule=$doctor->schedules()->first();
            $doctor->schedules()->update([
                'day' => $data['day'] ?? ($schdule ? $schdule->day:null),
                'start_time'  => $data['start_time'] ?? ($schdule ? $schdule->start_time:null),
                'end_time'      => $data['end_time'] ?? ($schdule ? $schdule->end_time:null),
                'price'      => $data['price'] ?? ($schdule ? $schdule->price:null),
            ]);
        }

         $doctor->load('user','schedules');
           if( $doctor->profile_photo){
                $doctor->profile_photo=asset('storage/'.$doctor->profile_photo);
            }
        return response()->json([
            'message' => 'Doctor updated successfully',
            'doctor' => $doctor->load('user'),
        ], 200);
    }

    public function createOffer(array $data){
             $user=auth('sanctum')->user();
        return Offer::create([
            'admin_id'=>$user->id,
            'title'=>$data['title'],
            'description'=>$data['description'],
            'discount_percentage'=>$data['discount_percentage'],
            'valid_from'=>$data['valid_from'],
            'valid_until'=>$data['valid_until'],
            'is_active'=>true,
        ]);
    }

    public function getItems(){
        $items=Item::orderBy('id','desc')->get();
        return $items;
    }

    public function useItem(int $itemId){
        return DB::transaction(function ()use ($itemId){
            $item=Item::find($itemId);
            if(!$item){
                return [
                    'message'=>'Item not found',
                ];
            }
            if($item->quantity>=1){
              $item->decrement('quantity',1);
              Inventory_logs::create([
                'item_id'=>$item->id,
                'type'=>'removal',
                'quantity_changed'=>1,
              ]);
              if($item->quantity<=$item->min_quantity){
                                $this->sendLowStockNotification($item);
              }
              return $item->fresh();
            }
        });
           
    }

        /**
         * Handle low stock notification for an item.
         * يبعت FCM push + يخزن الإشعار بالداتابيس عشان يظهر بالداشبورد (bell icon).
         */
        public function sendLowStockNotification(Item $item)
        {
            try{
                $superAdmin=User::where('role','super_admin')->first();

                $title = 'Low Stock Alert⚠️';
                $body  = "The item '{$item->name}' has reached its minimum quantity. Current quantity: {$item->quantity}";

                if($superAdmin && $superAdmin->fcm_token){
                    $token=$superAdmin->fcm_token;
                    $messaging = app('firebase.messaging');
                    $message = CloudMessage::fromArray([
                        'token'=>$token,
                        'notification'=>[
                            'title'=>$title,
                             'body' => $body,
                ],
                'data' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'notification_type' => 'low_stock',
                    'item_id' => (string) $item->id,
                ],
                'android' => [
                    'notification' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'importance' => 'HIGH',
                    ]
                ]
            ]);

            // 3. إرسال الإشعار
            $messaging->send($message);
        }

        // تخزين الإشعار بالداتابيس (بغض النظر عن وجود fcm_token) عشان يظهر بالداشبورد
        if ($superAdmin) {
            Notification::create([
                'user_id' => $superAdmin->id,
                'title'   => $title,
                'body'    => $body,
                'type'    => 'low_stock',
                'data'    => ['item_id' => $item->id],
            ]);
        }
    } catch (\Exception $e) {
        // في حال فشل الإشعار لأي سبب، لا نوقف عملية الخصم بل نتجاهل خطأ الإشعار أو نسجله
        Log::error('Failed to send low stock notification: ' . $e->getMessage());
    }
 }
}