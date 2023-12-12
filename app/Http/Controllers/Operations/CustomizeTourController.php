<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Test\Constraint\ResponseFormatSame;

class CustomizeTourController extends Controller
{
    //package upload for custom tour
    public function packageCustomTour(Request $request){
        $validateData = Validator::make($request->all(), [
            'package' => 'required',
            'adult' => 'required|numeric|gte:0',
            'extraBed' => 'required|numeric|gte:0',
            'childWithout' => 'required|numeric|gte:0'
        ], [
            'adult.gte' => 'The  adult amount value must be greater than 0.',
            'extraBed.gte' => 'The extraBed  amount value must be  greater than 0.',
            'childWithout.gte' => 'The childWithout amount value must be greater than 0.',
        ]);
        
      
        if($validateData->fails()){
            return response(["message" => $validateData->errors()->all()], 400);
        }

        //checks token 
        $tokenData = CommonController::checkToken($request->header('token'), [3]);
          if (!$tokenData) {
              return response()->json(['message' => 'Invalid Token'], 408);
          }
        //inserting the data in table
        $packages = DB::table('packagesCustomTour')->insert([
            'enquiryCustomId' => $request->enquiryCustomId,
            'package' => $request->package,
            'adult' => $request->adult,
            'extraBed' => $request->extraBed,
            'childWithout' => $request->childWithout
        ]);
        if($packages){
            return response(["message" => "Packages added successfully"], 200);
        }else{
            return response(["message" => "Something went wrong"], 500);
        }
    }

  

    //package upload
    public function packageUpload(Request $request){
        
            $validateData = Validator::make($request->all(), [
                'pdf' => 'required|mimes:pdf,xlsx,csv|max:2048',
            ]);
            if($validateData->fails()){
                return response()->json([$validateData->errors()->all()], 422);
            }
            //checks token
            $tokenData = CommonController::checkToken($request->header('token'), [3]);
            if (!$tokenData) {
                return response()->json(['message' => 'Invalid Token'], 408);
            }

            if ($request->file('pdf')) {
                $file = $request->file('pdf');
                $filename = date('YmdHi') . $file->getClientOriginalName();
                $file->move('pdf', $filename); 
                $pdfUrl = asset('pdf/' . $filename);
        
                return response()->json(['message' => 'Uploaded successfully', 'pdf' => $pdfUrl]);
            }
    }

    //confirmed custom tours
    public function confirmCustomTours(Request $request){
        //checks the token
        $tokenData = CommonController::checkToken($request->header('token'), [3]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $confirmCustom = DB::table('enquiryCustomTours')
        ->join('customTourDiscountDetails', 'enquiryCustomTours.enquiryCustomId', '=', 'customTourDiscountDetails.enquiryCustomId')
        ->join('customTourPaymentDetails', function($join) {
            $join->on('enquiryCustomTours.enquiryCustomId', '=', 'customTourPaymentDetails.enquiryCustomId')
                 ->where('customTourPaymentDetails.created_at', '=', DB::raw('(SELECT MAX(created_at) FROM customTourPaymentDetails AS subquery WHERE subquery.enquiryCustomId = customTourPaymentDetails.enquiryCustomId)'));
        })
        ->where('enquiryCustomTours.enquiryProcess', '=', 2)
        ->where('customTourPaymentDetails.status', 1)
        ->select('enquiryCustomTours.*', 'customTourDiscountDetails.*','customTourDiscountDetails.billingName', 'customTourPaymentDetails.advancePayment','customTourPaymentDetails.customPayDetailId', 'customTourPaymentDetails.balance');
        

        //searching by name
        if (!empty($request->search) || $request->search != "" || $request->search != null) {
            $search = $request->search;
            $confirmCustom->where(function ($q) use ($search) {
                $q->where('billingName', 'like', '%' . $search . '%');
            });
        }

        //pagination
        $confirmCustomTour = $confirmCustom->paginate($request->perPage == null ? 10 : $request->perPage);
        if($confirmCustomTour->isEmpty()){
            $confirmCustomArray = [];
        }
        $myObj = new \stdClass();
        foreach ($confirmCustomTour as $key => $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId ;
            $myObj->customPayDetailId = $value->customPayDetailId ;
            $myObj->groupName = $value->groupName;
            $myObj->billingName = $value->billingName;
            $myObj->phoneNo = $value->phoneNo;
            $myObj->tourPrice = $value->tourPrice;
            $myObj->gst = $value->gst;
            $myObj->tcs = $value->tcs;
            $myObj->grandTotal = $value->grandTotal;
            $myObj->advancePayment = $value->advancePayment;
            $myObj->balance = $value->balance;

            $confirmCustomArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $confirmCustomArray,
            'total' => $confirmCustomTour->total(),
            'currentPage' => $confirmCustomTour->currentPage(),
            'perPage' => $confirmCustomTour->perPage(),
            'nextPageUrl' => $confirmCustomTour->nextPageUrl(),
            'previousPageUrl' => $confirmCustomTour->previousPageUrl(),
            'lastPage' => $confirmCustomTour->lastPage()
        ), 200);
    }

    //today follow up
    public function enquiryCustomOperation(Request $request){
        $tokenData = CommonController::checkToken($request->header('token'), [3]); //sales,operations
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $today = now()->toDateString();
       //  dd($today);
        //listing
        $enquiryCustomList = DB::table('enquiryCustomTours')
           ->join('dropdowndestination', 'enquiryCustomTours.destinationId', 'dropdowndestination.destinationId')
           ->where('enquiryCustomTours.enquiryProcess', 1)
           ->whereDate('enquiryCustomTours.nextFollowUp', $today) 
           ->select('enquiryCustomTours.*', 'dropdowndestination.destinationName')
           ->paginate($request->perPage == null ? 10 : $request->perPage);
           if($enquiryCustomList->isEmpty()){
               $enquiryCustomArray =[];
           }
           $myObj = new \stdClass();
           foreach ($enquiryCustomList as $key => $value) {
               $myObj->enquiryCustomId = $value->enquiryCustomId;
               $myObj->enqDate = date('d-m-y', strtotime($value->created_at));
               $myObj->groupName = $value->groupName;
               $myObj->contactName = $value->contactName;
               $myObj->startDate = $value->startDate;
               $myObj->endDate = $value->endDate;
               $myObj->contact = $value->contact;
               $myObj->destinationName = $value->destinationName ;
               $myObj->pax = $value->adults + $value->child;
               $myObj->lastFollowUp = date('d-m-y', strtotime($value->created_at));
               $myObj->nextFollowUp = date('d-m-y', strtotime($value->nextFollowUp));

               $enquiryCustomArray[] = $myObj;
               $myObj =new \stdClass();
           }
           return  response()->json(array(
               'data' => $enquiryCustomArray,
               'total' => $enquiryCustomList->total(),
               'currentPage' => $enquiryCustomList->currentPage(),
               'perPage' => $enquiryCustomList->perPage(),
               'nextPageUrl' => $enquiryCustomList->nextPageUrl(),
               'previousPageUrl' => $enquiryCustomList->previousPageUrl(),
               'lastPage' => $enquiryCustomList->lastPage()
           ), 200);
    }

    //upcomingfollowCtOperation
    public function upcomingfollowCtOperation(Request $request){
        $tokenData = CommonController::checkToken($request->header('token'), [3]); //sales,operations
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $today = now()->toDateString();
        //listing
        $enquiryCustomList = DB::table('enquiryCustomTours')
           ->join('dropdowndestination', 'enquiryCustomTours.destinationId', 'dropdowndestination.destinationId')
           ->where('enquiryCustomTours.enquiryProcess', 1)
           ->where('enquiryCustomTours.nextFollowUp' ,'>', $today)
           ->select('enquiryCustomTours.*', 'dropdowndestination.destinationName')
           ->paginate($request->perPage == null ? 10 : $request->perPage);
           if($enquiryCustomList->isEmpty()){
               $enquiryCustomArray =[];
           }
           $myObj = new \stdClass();
           foreach ($enquiryCustomList as $value) {
               $myObj->enquiryCustomId = $value->enquiryCustomId;
               $myObj->enqDate = date('d-m-y', strtotime($value->created_at));
               $myObj->groupName = $value->groupName;
               $myObj->contactName = $value->contactName;
               $myObj->startDate = $value->startDate;
               $myObj->endDate = $value->endDate;
               $myObj->contact = $value->contact;
               $myObj->destinationName = $value->destinationName ;
               $myObj->pax = $value->adults + $value->child;
               $myObj->lastFollowUp = date('d-m-y', strtotime($value->created_at));
               $myObj->nextFollowUp = date('d-m-y', strtotime($value->nextFollowUp));

               $enquiryCustomArray[] = $myObj;
               $myObj =new \stdClass();
           }
           return  response()->json(array(
               'data' => $enquiryCustomArray,
               'total' => $enquiryCustomList->total(),
               'currentPage' => $enquiryCustomList->currentPage(),
               'perPage' => $enquiryCustomList->perPage(),
               'nextPageUrl' => $enquiryCustomList->nextPageUrl(),
               'previousPageUrl' => $enquiryCustomList->previousPageUrl(),
               'lastPage' => $enquiryCustomList->lastPage()
           ), 200);
    }
}
