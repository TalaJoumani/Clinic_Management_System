<?php
namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Medical_records;
use App\Models\Patient;
use App\Image\ImageUpload;
use App\Models\Location;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DoctorServices{
    
     protected ImageUpload $imageUpload;
   public function __construct(ImageUpload $imageUpload){
    $this->imageUpload=$imageUpload;
   }

public function getAppointmentForDoctor(){
    $doctor=auth('sanctum')->user();
    $doctorId=$doctor->id;
    $query=Appointment::with(['patient','location'])
    ->where('doctor_id',$doctorId)
    ->orderBy('appointment_time','asc')->get();
    return $query;
}


public function getMedicalRecord(int $userId): array
{
    // جلب المريض عن طريق user_id مش عن طريق id تبع جدول patients
$patient = Patient::with(['user' => function($q) { $q->select('id', 'first_name', 'last_name', 'gender', 'birth', 'email'); }])->where('user_id', $userId)->first();
    if (!$patient) {
        return [
            'status'  => 'error',
            'message' => 'patient not found',
        ];
    }

    $medicalRecords = Medical_records::where('patient_id', $userId) // استخدم id الحقيقي تبع المريض
    
        ->with(['doctor', 'appointment'])
        ->get()
        ->map(function ($record) {
            return [
                'diagnosis'             => $record->diagnosis,
                'prescription'          => $record->prescription,
                'tests'                 => $record->tests,
                'images'                => $record->images,
                'notes'                 => $record->notes,
                'appointment_time'      => optional($record->appointment)->appointment_time,
                'type'                  => optional($record->appointment)->type,
                'doctor_name'           => trim(
                    optional($record->doctor)->first_name . ' ' . optional($record->doctor)->last_name
                ),
                'doctor_specialization' => optional($record->doctor)->specialization,
            ];
        });
        

    return [
        'status'          => 'success',
        'patient_details' => $patient,
        'medical_records' => $medicalRecords,
    ];
}

public function updateMedicalRecord(array $data)
{
    $user = auth('sanctum')->user();
    $doctor = Doctor::where('user_id', $user->id)->first();

    if (!$doctor) {
        return [
            'message' => 'this account not doctor',
        ];
    }

    $appointmentId = $data['appointment_id'];
    Log::info('DEBUG UPDATE RECORD', [
        'auth_user_id' => $user->id,
        'appointment_id_sent' => $appointmentId,
    ]);

    // ⚠️ appointments.doctor_id فعلياً بيخزّن user_id (مش doctors.id)
    // فلازم نقارن مع $user->id مش $doctor->id
    $appointment = Appointment::where('id', $appointmentId)
        ->where('doctor_id', $user->id)
        ->first();

    if (!$appointment) {
        return [
            'status' => 'error',
            'message' => 'this appointment not found or not to this doctor'
        ];
    }

    $latsetAppointment = Appointment::where('doctor_id', $user->id)
        ->where('patient_id', $appointment->patient_id)
        ->where('appointment_time', '<=', now())
         ->latest('updated_at')
          ->first();       

    if (!$latsetAppointment || $latsetAppointment->id != $appointmentId) {
        return [
            'message' => 'this old appointment can not edite',
        ];
    }
    $medicalRecord = Medical_records::where('appointment_id', $appointmentId)
        ->where('doctor_id', $user->id)  
        ->first();

    if (!$medicalRecord) {
        return [
            'status' => 'error',
            'message' => 'this medical record not found for this appointment'
        ];
    }

    $imagePath = $medicalRecord->images;
    if (isset($data['images']) && $data['images']->isValid()) {
        if ($medicalRecord->images) {
            $this->imageUpload->delete($medicalRecord->images);
        }
        $Path = $this->imageUpload->upload($data['images'], 'patient-photo');
        $imagePath = asset('storage/' . $Path);
    }

    $medicalRecord->update([
        'diagnosis'    => $data['diagnosis'] ?? $medicalRecord->diagnosis,
        'prescription' => $data['prescription'] ?? $medicalRecord->prescription,
        'tests'        => $data['tests'] ?? $medicalRecord->tests,
        'images'       => $imagePath ?? $medicalRecord->images,
        'notes'        => $data['notes'] ?? $medicalRecord->notes,
    ]);

    return [
        'status' => 'success',
        'message' => 'تم تحديث السجل الطبي للموعد بنجاح.',
        'data' => $medicalRecord
    ];
}

public function getPatientLocation(int $appointmentId){
      $user = auth('sanctum')->user();
    $doctor = Doctor::where('user_id', $user->id)->first();

    if (!$doctor) {
        return [
            'message' => 'this account not doctor',
        ];
    }
    $appointment=Appointment::with(['patient'])
    ->where('id',$appointmentId)
    ->where('doctor_id',$user->id)
    ->first();
      if (!$appointment) {
        return [
            'status' => 'error',
            'message' => 'this appointment not found or not to this doctor'
        ];
    }
    $patientUser=$appointment->patient;
    $location=$patientUser?Location::where('user_id',$patientUser->id)->first():null;
    if(!$location){
        return response()->json([
            'message'=>'Location not found for this patient',
        ],404);
    }
    return response()->json([
        'patient_name'=>$patientUser->first_name??'unknown',
        'location'=>$location,
    ]);

}

public function getDoctorProfile(){
    $user = auth('sanctum')->user();
    $doctor = Doctor::with(['user','schedules'])->where('user_id', $user->id)->first();
    if(!$doctor){
        return response()->json(['message'=>'this account not doctor'],403);
    }
    if($doctor->profile_photo){
        $doctor->profile_photo = asset('storage/'.$doctor->profile_photo);
    }
    return [
        'data' => $doctor,
    ];
}

public function updateDoctorProfile(array $data){
    $user = auth('sanctum')->user();
    $doctor = Doctor::with(['user','schedules'])->where('user_id', $user->id)->first();
    if(!$doctor){
        return response()->json(['message'=>'this account not doctor'],403);
    }
    $imagePath = $doctor->profile_photo;
    if(isset($data['profile_photo']) && $data['profile_photo']->isValid()){
        if($doctor->profile_photo){
            $this->imageUpload->delete($doctor->profile_photo);
        }
        $imagePath = $this->imageUpload->upload($data['profile_photo'],'doctor_photo');
    }

    $doctor->update([
        'specialization' => $data['specialization'] ?? $doctor->specialization,
        'home_visit'     => $data['home_visit'] ?? $doctor->home_visit,
        'profile_photo'  => $imagePath,
    ]);

    if(isset($data['price'])){
        $doctor->schedules()->update(['price' => $data['price']]);
    }

    if(isset($data['password']) && !empty($data['password'])){
        User::whereKey($user->id)->update([
            'password' => Hash::make($data['password']),
        ]);
    }

    $doctor->load(['user','schedules']);
    if($doctor->profile_photo){
        $doctor->profile_photo = asset('storage/'.$doctor->profile_photo);
    }
    return [
        'message' => 'Doctor profile updated successfully',
        'doctor' => $doctor,
    ];
}

public function getDoctorSchedule(){
    $user = auth('sanctum')->user();
    $doctor = Doctor::with('schedules')->where('user_id', $user->id)->first();
    if(!$doctor){
        return response()->json(['message'=>'this account not doctor'],403);
    }
    return [
        'data' => [
            'working_days' => $doctor->schedules,
            'days_off'     => [],
            'peak_hours'   => [],
        ],
    ];
}

public function updateDoctorSchedule(array $data){
    $user = auth('sanctum')->user();
    $doctor = Doctor::where('user_id', $user->id)->first();
    if(!$doctor){
        return response()->json(['message'=>'this account not doctor'],403);
    }
    $workingDays = $data['working_days'] ?? [];
    if(!empty($workingDays) && is_array($workingDays)){
        $doctor->schedules()->delete();
        foreach($workingDays as $day){
            if(!is_array($day) || !isset($day['day']) || !isset($day['start_time']) || !isset($day['end_time'])){
                continue;
            }
            $doctor->schedules()->create([
                'day'        => $day['day'],
                'start_time' => $day['start_time'],
                'end_time'   => $day['end_time'],
                'price'      => $day['price'] ?? 100,
            ]);
        }
    }
    return [
        'message' => 'Schedule updated successfully',
    ];
}

public function getDoctorInvoices(){
    $user = auth('sanctum')->user();
    $appointments = Appointment::with(['patient','payment'])
        ->where('doctor_id', $user->id)
        ->orderBy('appointment_time','desc')
        ->get();

    $invoices = [];
    foreach($appointments as $appointment){
        $payment = $appointment->payment;
        if(!$payment){
            continue;
        }
        $patientName = $appointment->patient
            ? trim($appointment->patient->first_name . ' ' . $appointment->patient->last_name)
            : 'Unknown';

        $status = match ($payment->status) {
            'fully_paid'    => 'paid',
            'partially_paid'=> 'partial',
            default         => 'unpaid',
        };

        $invoices[] = [
            'id'               => $payment->id,
            'patient_name'     => $patientName,
            'appointment_time' => $appointment->appointment_time,
            'type'             => $appointment->type,
            'status'           => $status,
            'total'            => $payment->total_amount,
            'deposit'          => $payment->amount_paid,
            'remaining'        => $payment->remaining_amount,
            'paid_at'          => $payment->status === 'fully_paid' ? $payment->updated_at : null,
        ];
    }
    return [
        'message' => $invoices,
    ];
}

public function getDoctorPatients(){
    $user = auth('sanctum')->user();
    $appointments = Appointment::with('patient')
        ->where('doctor_id', $user->id)
        ->orderBy('appointment_time','desc')
        ->get();

    $patients = [];
    foreach($appointments->groupBy('patient_id') as $patientId => $patientAppointments){
        $first = $patientAppointments->first();
        $patient = $first ? $first->patient : null;
        if(!$patient){
            continue;
        }
        $patients[] = [
            'user_id'              => $patient->id,
            'first_name'           => $patient->first_name,
            'last_name'            => $patient->last_name,
            'gender'               => $patient->gender,
            'phone'                => $patient->phone,
            'email'                => $patient->email,
            'last_appointment_id'  => $first->id,
            'last_visit'           => $first->appointment_time,
            'appointments_count'   => $patientAppointments->count(),
        ];
    }
    return [
        'message' => $patients,
    ];
}

public function getDoctorConsultations(){
    $user = auth('sanctum')->user();
    $appointments = Appointment::with(['patient','location'])
        ->where('doctor_id', $user->id)
        ->where('type','online')
        ->orderBy('appointment_time','asc')
        ->get();
    return [
        'message' => $appointments,
    ];
}

public function endConsultation(array $data){
    $paymentService = new PaymentServices();
    $result = $paymentService->completeFinalPayment($data['appointment_id']);
    return [
        'message' => 'Consultation ended and appointment completed',
        'data' => $result,
    ];
}
}