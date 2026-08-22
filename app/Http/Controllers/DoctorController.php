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

    public function getDoctorProfile(){
        $result=$this->doctorServices->getDoctorProfile();
        return $result;
    }

    public function updateDoctorProfile(Request $request){
        $validatedData = $request->validate([
            'specialization' => 'sometimes|string|max:255',
            'home_visit'     => 'sometimes|boolean',
            'price'          => 'sometimes|numeric|min:0',
            'password'       => 'sometimes|string|min:8|confirmed',
            'profile_photo'  => 'sometimes|mimes:jpeg,png,jpg,gif',
        ]);
        $result=$this->doctorServices->updateDoctorProfile($validatedData);
        return $result;
    }

    public function getDoctorSchedule(){
        $result=$this->doctorServices->getDoctorSchedule();
        return $result;
    }

    public function updateDoctorSchedule(Request $request){
        $validatedData = $request->validate([
            'working_days' => 'sometimes|array',
            'working_days.*.day' => 'required|in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,All',
            'working_days.*.start_time' => 'required|date_format:H:i',
            'working_days.*.end_time' => 'required|date_format:H:i',
            'working_days.*.price' => 'sometimes|numeric|min:0',
            'days_off' => 'sometimes|array',
            'peak_hours' => 'sometimes|array',
        ]);
        $result=$this->doctorServices->updateDoctorSchedule($validatedData);
        return $result;
    }

    public function getDoctorInvoices(){
        $result=$this->doctorServices->getDoctorInvoices();
        return $result;
    }

    public function getDoctorPatients(){
        $result=$this->doctorServices->getDoctorPatients();
        return $result;
    }

    public function getDoctorConsultations(){
        $result=$this->doctorServices->getDoctorConsultations();
        return $result;
    }

    public function endConsultation(Request $request){
        $validatedData = $request->validate([
            'appointment_id' => 'required|integer',
        ]);
        $result=$this->doctorServices->endConsultation($validatedData);
        return $result;
    }
}
