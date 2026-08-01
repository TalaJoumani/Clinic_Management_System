<?php
namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Medical_records;
use App\Models\Patient;
use App\Image\ImageUpload;

class DoctorServices{
    
     protected ImageUpload $imageUpload;
   public function __construct(ImageUpload $imageUpload){
    $this->imageUpload=$imageUpload;
   }

public function getAppointmentForDoctor($doctorId){
    $query=Appointment::with(['patient','location'])
    ->where('doctor_id',$doctorId)
    ->where('status','completed')
    ->whereDate('appointment_time',now()->toDateString())
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

public function updateMedicalRecord(array $data, int $appointmentId)
{
    $user=auth('sanctum')->user();
    $doctor=Doctor::where('user_id',$user->id)->first();
    if(!$doctor){
        return [
            'message'=>'this account not doctor',
        ];
    }
    $doctorId=$doctor->id;
    $latsetAppointment=Appointment::where('doctor_id',$doctorId)
    ->latest('appointment_time')->first();
    if(!$latsetAppointment || $latsetAppointment->id!==$appointmentId){
        return [
            'message'=>'this old appointment can not edite ',
        ];
    }
    // 1. التحقق من أن الموعد يتبع لهذا الطبيب حصراً، وأنه ليس موعداً قديماً (حسب التاريخ أو الحالة)
    $appointment = Appointment::where('id', $appointmentId)
        ->where('doctor_id', $doctorId)
        ->first();

    if (!$appointment) {
        return [
            'status' => 'error',
            'message' => 'الموعد غير موجود، أو أنه لا يتبع لهذا الطبيب.'
        ];
    }

    // 2. جلب السجل الطبي المرتبط حصراً بهذا الموعد
    $medicalRecord = Medical_records::where('appointment_id', $appointmentId)
        ->where('doctor_id', $doctorId)
        ->first();

    if (!$medicalRecord) {
        return [
            'status' => 'error',
            'message' => 'this medical recoed not found for this appointment'
        ];
    }
    $imagePath=$medicalRecord->images;
    if(isset($data['images']) && $data['images']->isValid()){
        if($medicalRecord->images){
            $this->imageUpload->delete($medicalRecord->images);
        }
        $Path=$this->imageUpload->upload($data['images'],'patient-photo');
        $imagePath=asset('storage/'.$Path);
    }

   $medicalRecord->update([
        'diagnosis'    =>  $data['diagnosis'] ??$medicalRecord->diagnosis ,
        'prescription' =>  $data['prescription'] ?? $medicalRecord->prescription,
        'tests'        =>  $data['tests'] ??  $medicalRecord->tests,
        'images'       =>  $imagePath?? $medicalRecord->images,
        'notes'        =>  $data['notes'] ?? $medicalRecord->notes,
    ]);
    $medicalRecord->save();

    return [
        'status' => 'success',
        'message' => 'تم تحديث السجل الطبي للموعد بنجاح.',
        'data' => $medicalRecord
    ];
}

}