<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tour;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TourController extends Controller
{
    //add-tour
    public function addTour(Request $request){
        $validateData = Validator::make($request->all(), [
            'tourName' => 'required',
            'tourCode' => 'required',
            'tourType' => 'required',
            'tourPrice' => 'required',
            'departureType' => 'required',
            'departureCity' => 'required',
            'destination' => 'required',
            'startDate' => 'required',
            'endDate' => 'required',
            'duration' => 'required',
            'days' => 'required',
            'night' => 'required',
            'meal' => 'required',
            'mealType' => 'required',
            'manager' => 'required',
            // 'onePay' => 'required',
            // 'earlyOnePay' => 'required',
            // 'byg' => 'required',
        ]);
        
        if($validateData->fails()){
            return response(["message" => $validateData->errors()->all()], 422);
        }
        //storing 
        $tourDetails = DB::table('tours')->insert([
            'tourName' => $request->tourName,
            'tourCode' => $request->tourCode,
            'tourType' => $request->tourType,
            'tourPrice' => $request->tourPrice,
            'departureType' => $request->departureType,
            'departureCity' => $request->departureCity,
            'destination' => $request->destination,
            'startDate' => $request->startDate,
            'endDate' => $request->endDate,
            'duration' => $request->duration,
            'days' => $request->days,
            'night' =>  $request->night,
            'seats' => $request->seats,
            'meal' => $request->meal,
            'mealType' => $request->mealType,
            'manager' => $request->manager
        ]);
        if($tourDetails){
            return response(['message' => 'Tour added successfully'], 200);
        } else {
            return response(['message' => 'Something went wrong'], 500);
        }
    }

    //enquiry-form
    public function enquiry(Request $request){
        $validateData = Validator::make($request->all(), [
            'guestName' => 'required',
            'contactNo' => 'required|digits:10',
            'tourName'  => 'required',
            'email' => 'required|email',
            'paxNo' => 'required|numeric',
            'adult' => 'required|numeric',
            'child' => 'required|numeric',
            'duration' => 'required'
        ]);
        if($validateData->fails()){
            return response(["message" => $validateData->errors()->all()], 422);
        }

        $enquiryDetails = DB::table('enquiry')->insert([
            'guestName' => $request->guestName,
            'groupName' => $request->groupName,
            'tourType'  => $request->tourType,
            'contactNo' => $request->contactNo,
            'email' => $request->email,
            'tourName' => $request->tourName,
            'duration' => $request->duration,
            'paxNo' => $request->paxNo,
            'adult' => $request->adult,
            'child' => $request->child,
            // 'familyHeadNo' => $request->familyHeadNo,
            // 'reference' => $request->reference,
            // 'guestReferId' => $request->guestReferId,
            // 'priority' => $request->priority,
            // 'nextFollowUp' => $request->nextFollowUp
        ]);
        if($enquiryDetails){
            return response(["message" => "Enquiry added successfully."], 200);
        }else{
            return response(["message" => "Something went wrong"], 500);
        }
    }

    //booking tour gt
    public function bookings(Request $request){
        $validateData = Validator::make($request->all(),[
            // 'tourName' => 'required',
            // 'tourCode' => 'required',
            // 'paxNo' => 'required|numeric',
            'enquiryId' => 'required',
            // 'tourId' => 'required'
        ]);
        if($validateData->fails()){
            return response(["message" => $validateData->errors()->all()], 422);
        }
        
     
        $enquiry = DB::table('enquiry')->where('enquiryId', $request->enquiryId)->first();
        // dd($enquiry);
        if(!$enquiry){
            return response(["message" => "Enquiry not found"], 404);
        }

        $tour = DB::table('tours')->where('tourName', $enquiry->tourName)->first();

        if(!$tour){
            return response(["message" => "Tour not found"], 404);
        }

        $guestDetailsId = DB::table('guestdetails')->insertGetId([
            'name' => $enquiry->guestName, 
            'contactNo' => $enquiry->contactNo, 
            'enquiryId' => $request->enquiryId,
        ]);

        $bookingDetails = DB::table('bookings')->insert([
            'tourName' => $enquiry->tourName,
            'tourCode' => $tour->tourCode,
            'paxNo' => $request->paxNo,
            'destination' => $request->destination
        ]);
        if ($bookingDetails) {
            return response(["message" => "Booking created successfully"], 200);
        } else {
            return response(["message" => "Booking creation failed"], 500);
        }
    }
}
