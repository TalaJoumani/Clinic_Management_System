<?php

namespace App\Http\Controllers;

use App\Services\PatientServices;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    protected PatientServices $patientServices;
    public function __construct(PatientServices $patientServices)
    {
       $this->patientServices=$patientServices;
    }

    public function getAllDoctors(){
        $result=$this->patientServices->getAllDoctors();
        return $result;
    }

    public function getSpcialization(){
        $result=$this->patientServices->getSpcialization();
        return $result;
    }

    public function filterDoctor(Request $request){ 
        $result=$this->patientServices->filterDoctor( $request);
        return $result;
    }

    public function getMedicaleRecord(){
        $result=$this->patientServices->getMedicaleRecord();
        return $result;
        }

        public function exportMedicalRecords()
        {
            $result = $this->patientServices->exportMedicalRecords();
            return $result;
        }

        public function getAppointmentPatient(){
            $result = $this->patientServices->getAppointmentPatient();
            return $result;
        }

        public function getActiveOffers(){
            $result=$this->patientServices->getActiveOffers();
            return response()->json([
                'message'=>$result,
            ]);
        }
}
