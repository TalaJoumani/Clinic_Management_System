<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMedicalRecordRequest;
use App\Services\DoctorServices;

use Illuminate\Http\Request;

class DoctorController extends Controller
{
    protected DoctorServices $doctorServices;
    public function __construct(DoctorServices $doctorServices)
    {
        $this->doctorServices = $doctorServices;
    }

    public function getAppointmentForDoctor(){
        $appointment=$this->doctorServices->getAppointmentForDoctor();
        return response()->json([
            'data'=>$appointment,
        ]);
    }

    public function getMedicalRecord(int $patientId){
        $data=$this->doctorServices->getMedicalRecord($patientId);
        return response()->json([
            'data'=>$data
        ]);
    }

    public function updateMedicalRecord(UpdateMedicalRecordRequest $updateMedicalRecordRequest){
        $result=$this->doctorServices->updateMedicalRecord($updateMedicalRecordRequest->validated());
        return response()->json([
            'data'=>$result,
        ]);
    }

    public function getPatientLocation(int $appointmentId){
        $result=$this->doctorServices->getPatientLocation($appointmentId);
        return $result;
    }
}
