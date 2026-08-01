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

    public function getAppointmentForDoctor($doctorId){
        $appointment=$this->doctorServices->getAppointmentForDoctor($doctorId);
        return response()->json([
            'data'=>$appointment,
        ]);
    }

    public function getMedicalRecord($patientId){
        $data=$this->doctorServices->getMedicalRecord($patientId);
        return response()->json([
            'data'=>$data
        ]);
    }

    public function updateMedicalRecord(UpdateMedicalRecordRequest $updateMedicalRecordRequest,$appointmentId){
        $result=$this->doctorServices->updateMedicalRecord($updateMedicalRecordRequest->validated(),$appointmentId);
        return response()->json([
            'data'=>$result,
        ]);
    }
}
