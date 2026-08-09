<?php
namespace App\Services;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Doctor;
use App\Models\Medical_records;
use App\Models\Offer;
use App\Models\Patient;
use Carbon\Carbon;

class PatientServices{
    public function getAllDoctors(){
        $user=auth('sanctum')->user();
        if(!$user){
            return response()->json([
                'message'=>'this user not found',
            ],403);
        }
        $patient=Patient::where('user_id',$user->id)->first();
        if(!$patient){
             return response()->json([
                'message'=>'this services only for patient',
            ],403);
        }
        $doctors=Doctor::with(['user','schedules'])->where('is_available',1)->get();
        $doctors->transform(function ($doctor) {
            if($doctor->profile_photo){
                $doctor->profile_photo=asset('storage/'.$doctor->profile_photo);
            }
            return $doctor;
        });
        return response()->json([
            'message'=>$doctors,
        ]);
    }

    public function getSpcialization(){
             $user=auth('sanctum')->user();
        if(!$user){
            return response()->json([
                'message'=>'this user not found',
            ],403);
        }
        $patient=Patient::where('user_id',$user->id)->first();
        if(!$patient){
             return response()->json([
                'message'=>'this services only for patient',
            ],403);
        }
        $Spcializations=Doctor::select('specialization')->distinct()->get();
        return response()->json([
            'message'=>$Spcializations,
        ]);
    }

public function filterDoctor(Request $request)
{
    $user = auth('sanctum')->user();
    
    if ($user->role !== 'patient') {
        return response()->json([
            'message' => 'this service only for patient',
        ], 403);
    }

    $query = Doctor::with(['user', 'schedules']);

    if ($request->filled('specialization')) {
        $query->where('specialization', 'like', '%' . $request->specialization . '%');
    }

    if ($request->filled('gender')) {
        $query->whereHas('user', function ($q) use ($request) {
            $q->where('gender', $request->gender);
        });
    }

    $doctors = $query->get();

    if ($doctors->isEmpty()) {
        return response()->json([
            'status' => true,
            'message' => 'No doctors found matching the criteria',
            'data' => []
        ], 200);
    }

    return response()->json([
        'status' => true,
        'data' => $doctors
    ], 200);
}

public function getMedicaleRecord(){
         $user=auth('sanctum')->user();
        if(!$user || !$user->patient){
           return response()->json([
                'message'=>'this services only for patient',
            ],403);
        }
      $patientId=$user->id;
       $medicalRecords = Medical_records::where('patient_id', $patientId) // استخدم id الحقيقي تبع المريض
    
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

      return response()->json([
        'message'=>$medicalRecords,
      ]);
}

public function exportMedicalRecords(){
      $user=auth('sanctum')->user();
        if(!$user || !$user->patient){
           return response()->json([
                'message'=>'this services only for patient',
            ],403);
        }
      $patientId=$user->patient->id;
      $medicalRecords=Medical_records::where('patient_id', $user->id) // استخدم id الحقيقي تبع المريض
    
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
        $data=[
            'user'=>$user,
            'medicalRecords'=>$medicalRecords,
            'date'=>now()->format('Y-m-d H:i:s'),
        ];
        $pdf=Pdf::loadView('Pdf.medical_Record',$data);
        return $pdf->download('medical_records.pdf');
}

     public function getAppointmentPatient(){
          $user=auth('sanctum')->user();
        if(!$user || !$user->patient){
           return response()->json([
                'message'=>'this services only for patient',
            ],403);
        }
      $patientId=$user->id;
      $appointments=Appointment::with(['doctor.user','doctor'])->where('patient_id',$patientId)
      ->orderBy('appointment_time','desc')
      ->get();
      return response()->json([
        'count'=>$appointments->count(),
        'message'=>$appointments,
      ]);
     }

     public function getActiveOffers(){
          $user=auth('sanctum')->user();
        if(!$user || !$user->patient){
           return response()->json([
                'message'=>'this services only for patient',
            ],403);
        }
        $now=Carbon::now();
        $offer=Offer::where('is_active',true)
        ->whereDate('valid_from','<=',$now)
        ->whereDate('valid_until','>=',$now)->get();
        return response()->json([
            'message'=>$offer,
        ]);
        }
}