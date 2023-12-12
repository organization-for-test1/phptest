<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\CommonController;

class GroupController extends Controller
{
    //confirmed group listing
    public function confirmedGroupTourList(Request $request){
        //cheks token 
        $tokenData = CommonController::checkToken($request->header('token'), [3]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $confirmedList = DB::table('enquirygrouptours')
        ->where('enquirygrouptours.enquiryProcess', '=', 2)
        ->join('grouptours', 'enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
        ->select('grouptours.*','enquirygrouptours.*')
        ->orderBy('enquirygrouptours.enquiryGroupId', 'desc');

        $confirmedList->where(function ($q) use ($request) {
            if (!empty($request->tourName)) {
                $q->orWhere('tourName', 'like', '%' . $request->tourName . '%');
            }
            if (!empty($request->tourCode)) {
                $q->orWhere('tourCode', 'like', '%' . $request->tourCode . '%');
            }
            if (!empty($request->tourType)) {
                $q->orWhere('tourType', 'like', '%' . $request->tourType . '%');
            }
            if (!empty($request->startDate)) {
                $q->orWhere('startDate', 'like', '%' . $request->startDate . '%');
            }
            if (!empty($request->endDate)) {
                $q->orWhere('endDate', 'like', '%' . $request->endDate . '%');
            }
            
        });
        // dd($confirmedList);
        $confirmGroupTour = $confirmedList->paginate($request->perPage== null? 10: $request->perPage);
        if($confirmGroupTour->isEmpty()){
            $confirmGroupTourArray = [];
        }
        $myObj = new \stdClass();
        
        foreach ($confirmGroupTour as  $value) {
            $myObj->enquiryGroupId = $value->enquiryGroupId;
            $myObj->tourName = $value->tourName;
            $myObj->tourCode = $value->tourCode;
            $myObj->tourTypeId  = $value->tourTypeId;
            $myObj->startDate = $value->startDate;
            $myObj->endDate = $value->endDate;
            // Calculate the duration using Carbon
            $startDate = Carbon::parse($value->startDate);
            $endDate = Carbon::parse($value->endDate);
            $duration = $startDate->diffInDays($endDate);
            $myObj->duration = $duration;
            $myObj->totalSeats = $value->totalSeats;
            $groupTourId = $value->groupTourId;
            $enquiryGroupTour = DB::table('enquirygrouptours')
            ->where('enquiryProcess', 2)
            ->where('groupTourId', $groupTourId)
            ->selectRaw('SUM(adults + child) as totalGuests')
            ->first();
            $availableSeats = $myObj->totalSeats - $enquiryGroupTour->totalGuests;
            $bookedSeats = $myObj->totalSeats - $availableSeats;
            $myObj->seatsBooked = $bookedSeats;
            $myObj->seatsAval = $availableSeats;
           

            $confirmGroupTourArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $confirmGroupTourArray,
            'total' => $confirmGroupTour->total(),
            'currentPage' => $confirmGroupTour->currentPage(),
            'perPage' => $confirmGroupTour->perPage(),
            'nextPageUrl' => $confirmGroupTour->nextPageUrl(),
            'previousPageUrl' => $confirmGroupTour->previousPageUrl(),
            'lastPage' => $confirmGroupTour->lastPage()
        ), 200);
    }

    //guest details confirmed group tours
    public function guestConfirmGroupTour(Request $request){
         //cheks token 
         $tokenData = CommonController::checkToken($request->header('token'), [3]);
         if (!$tokenData) {
             return response()->json(['message' => 'Invalid Token'], 408);
         }
         
        $guestDetails = DB::table('enquirygrouptours')
            ->join('grouptourdiscountdetails', 'enquirygrouptours.enquiryGroupId', 'grouptourdiscountdetails.enquiryGroupId')
            ->where('enquirygrouptours.enquiryProcess', 2)
            ->select('enquirygrouptours.*', 'grouptourdiscountdetails.*')
            ->paginate($request->perPage == null ? 10 : $request->perPage);
            if($guestDetails->isEmpty()){
                $guestDetailsArray = [];
            }
            $myObj = new \stdClass();
            foreach ($guestDetails as $key => $value) {
            $myObj->guestName = $value->guestName;
            $myObj->contact = $value->contact;
            $myObj->tourPrice = $value->tourPrice;
            $myObj->additionalDis = $value->additionalDis;
            $myObj->discountPrice = $value->discountPrice;
            $myObj->gst = $value->gst;
            $myObj->tcs = $value->tcs;
            $myObj->grandTotal = $value->grandTotal;

            $guestDetailsArray[] = $myObj;
            $myObj = new \stdClass();

        }
        return  response()->json(array(
            'data' => $guestDetailsArray,
            'total' => $guestDetails->total(),
            'currentPage' => $guestDetails->currentPage(),
            'perPage' => $guestDetails->perPage(),
            'nextPageUrl' => $guestDetails->nextPageUrl(),
            'previousPageUrl' => $guestDetails->previousPageUrl(),
            'lastPage' => $guestDetails->lastPage()
        ), 200);

    }

    //today upcoming list
    public function todayFollowList(Request $request){
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [3]); //OPERATIONS
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $today = now()->toDateString();
        // dd($today);

        $groupTourInquiry = DB::table('enquirygrouptours')
            ->join('grouptours','enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
            ->where('enquirygrouptours.enquiryProcess', 1)
            ->whereDate('enquirygrouptours.nextFollowUp', $today)  
            ->select('enquirygrouptours.*', 'grouptours.tourName','grouptours.startDate','grouptours.endDate')
            ->orderBy('enquirygrouptours.enquiryGroupId', 'desc');
            // ->paginate($request->perPage == null ? 10 : $request->perPage);
    
            if ($request->startDate != '' && $request->endDate != '') {
                $start_datetime = Carbon::parse($request->startDate)->startOfDay();
                $end_datetime = Carbon::parse($request->endDate)->endOfDay();
                
                $groupTourInquiry->where(function($query) use ($start_datetime, $end_datetime) {
                    $query->where('startDate', '>=', $start_datetime)
                          ->where('endDate', '<=', $end_datetime);
                });
            } elseif ($request->startDate != '') {
                $start_datetime = Carbon::parse($request->startDate)->startOfDay();
                $groupTourInquiry->where('startDate', '>=', $start_datetime);
            } elseif ($request->endDate != '') {
                $end_datetime = Carbon::parse($request->endDate)->endOfDay();
                $groupTourInquiry->where('endDate', '<=', $end_datetime);
            }  elseif (!empty($request->search)) {
                $search = $request->search;
                $groupTourInquiry->where('guestName', 'like', '%' . $search . '%');
            }
            elseif (!empty($request->search)) {
                $search = $request->search;
                $groupTourInquiry->where('tourName', 'like', '%' . $search . '%');
            }

        //pagination
        $groupTourInquiries  = $groupTourInquiry->paginate($request->perPage == null ? 10 : $request->perPage);

        if ($groupTourInquiries->isEmpty()) {
            $groupTour_array = [];
        }

        $myObj = new \stdClass();
        foreach ($groupTourInquiries as $key => $value) {
            $myObj->enquiryGroupId = $value->enquiryGroupId;
            $myObj->enquiryDate = date('d-m-y', strtotime($value->created_at));
            $myObj->guestName = $value->guestName;
            $myObj->contact = $value->contact;
            $myObj->tourName = $value->tourName;
            $myObj->startDate = date('d-m-y', strtotime($value->startDate));
            $myObj->endDate = date('d-m-y', strtotime($value->endDate));
            $myObj->paxNo = $value->adults + $value->child;
            $myObj->lastFollowUp = date('d-m-y', strtotime($value->created_at));
            $myObj->nextFollowUp = date('d-m-y', strtotime($value->nextFollowUp));


           
            $groupTour_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $groupTour_array,
            'total' => $groupTourInquiries->total(),
            'currentPage' => $groupTourInquiries->currentPage(),
            'perPage' => $groupTourInquiries->perPage(),
            'nextPageUrl' => $groupTourInquiries->nextPageUrl(),
            'previousPageUrl' => $groupTourInquiries->previousPageUrl(),
            'lastPage' => $groupTourInquiries->lastPage()
        ), 200);
    }
    
    //upcoming group tour list 
    public function upcomingListGt(Request $request){
        $tokenData = CommonController::checkToken($request->header('token'), [3]); //OPERATIONS
          if (!$tokenData) {
              return response()->json(['message' => 'Invalid Token'], 408);
          }
          $today = now()->toDateString();

          $groupTour = DB::table('enquirygrouptours')
                ->join('grouptours','enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
                ->where('enquirygrouptours.enquiryProcess', 1)
                ->where('enquirygrouptours.nextFollowUp', '>', $today)
                ->select('enquirygrouptours.*', 'grouptours.tourName','grouptours.startDate','grouptours.endDate')
                ->orderBy('enquirygrouptours.enquiryGroupId', 'desc')
                ->paginate($request->perPage == null ? 10 : $request->perPage);
      
          if ($groupTour->isEmpty()) {
              $groupTour_array = [];
          }
         
  
          $myObj = new \stdClass();
          foreach ($groupTour as $key => $value) {
              $myObj->enquiryGroupId = $value->enquiryGroupId;
              $myObj->enquiryDate = date('d-m-y', strtotime($value->created_at));
              $myObj->guestName = $value->guestName;
              $myObj->contact = $value->contact;
              $myObj->tourName = $value->tourName;
              $myObj->startDate = date('d-m-y', strtotime($value->startDate));
              $myObj->endDate = date('d-m-y', strtotime($value->endDate));
              $myObj->paxNo = $value->adults + $value->child;
              $myObj->lastFollowUp = date('d-m-y', strtotime($value->created_at));
              $myObj->nextFollowUp = date('d-m-y', strtotime($value->nextFollowUp));
  
             
              $groupTour_array[] = $myObj;
              $myObj = new \stdClass();
          }
          return  response()->json(array(
              'data' => $groupTour_array,
              'total' => $groupTour->total(),
              'currentPage' => $groupTour->currentPage(),
              'perPage' => $groupTour->perPage(),
              'nextPageUrl' => $groupTour->nextPageUrl(),
              'previousPageUrl' => $groupTour->previousPageUrl(),
              'lastPage' => $groupTour->lastPage()
          ), 200);
    }
}
