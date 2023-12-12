<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CommonController;

class AccCTController extends Controller
{
    //confirmed payment listing custom tour
    public function confirmPayListCT(Request $request){
        $validateData = Validator::make($request->All(), [
            "enquiryCustomId" => "required|numeric",
            "enquiryDetailCustomId" => "required|numeric"
        ]);
        if($validateData->fails()){
            return response()->json(["message" => $validateData->errors()->all()], 400);
        }

        //confirm pay
        $confirmPayCt = DB::table('customTourPaymentDetails')
            ->join('customTourEnquiryDetails', 'customTourPaymentDetails.enquiryCustomId', '=', 'customTourEnquiryDetails.enquiryCustomId')
            ->join('customTourDiscountDetails', 'customTourPaymentDetails.enquiryCustomId', '=', 'customTourDiscountDetails.enquiryCustomId')
            ->join('enquiryCustomTours', 'customTourPaymentDetails.enquiryCustomId', '=', 'enquiryCustomTours.enquiryCustomId')
            ->where('customTourPaymentDetails.status', 1)
            ->where('customTourPaymentDetails.enquiryCustomId', $request->enquiryCustomId)
            // ->where('customTourPaymentDetails.enquiryDetailCustomId', $request->enquiryDetailCustomId)
            ->select('customTourDiscountDetails.tourPrice','customTourDiscountDetails.additionalDis',
            'customTourDiscountDetails.discountPrice','customTourDiscountDetails.gst','customTourDiscountDetails.tcs',
            'customTourDiscountDetails.grandTotal','customTourPaymentDetails.balance','customTourPaymentDetails.customPayDetailId', 'customTourPaymentDetails.advancePayment','customTourPaymentDetails.customDisId','enquiryCustomTours.*',
            'customTourEnquiryDetails.enquiryDetailCustomId')
           
            ->paginate($request->perPage == null ? 10 : $request->perPage);

        if($confirmPayCt->isEmpty()){
            $confirmCustomArray = [];
        }
        $myObj = new \stdClass();
        foreach ($confirmPayCt as  $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId ;
            $myObj->enquiryDetailCustomId = $value->enquiryDetailCustomId;
            $myObj->customDisId = $value->customDisId;
            $myObj->groupName = $value->groupName;
            $myObj->contactName = $value->contactName;
            $myObj->contact = $value->contact;
            $myObj->tourPrice = $value->tourPrice;
            $myObj->additionalDis = $value->additionalDis;
            $myObj->discountPrice = $value->discountPrice;
            $myObj->gst = $value->gst;
            $myObj->tcs = $value->tcs;
            $myObj->grandTotal = $value->grandTotal;
            $myObj->advancePayment = $value->advancePayment;
            $myObj->balance = $value->balance;
            $myObj->customPayDetailId = $value->customPayDetailId ;


            $confirmCustomArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $confirmCustomArray,
            'total' => $confirmPayCt->total(),
            'currentPage' => $confirmPayCt->currentPage(),
            'perPage' => $confirmPayCt->perPage(),
            'nextPageUrl' => $confirmPayCt->nextPageUrl(),
            'previousPageUrl' => $confirmPayCt->previousPageUrl(),
            'lastPage' => $confirmPayCt->lastPage()
        ), 200);
    }

    //pending payment lists custom tour
    public function pendingPayListCT(Request $request){
        $confirmCustom = DB::table('enquiryCustomTours')
        ->join('customTourDiscountDetails', 'enquiryCustomTours.enquiryCustomId', '=', 'customTourDiscountDetails.enquiryCustomId')
        ->join('customTourPaymentDetails', 'enquiryCustomTours.enquiryCustomId', '=', 'customTourPaymentDetails.enquiryCustomId')
        ->where('customTourPaymentDetails.status', 0)
        ->where('customTourPaymentDetails.enquiryCustomId', $request->enquiryCustomId)
        ->where('customTourPaymentDetails.enquiryDetailCustomId', $request->enquiryDetailCustomId)
        ->select('enquiryCustomTours.*', 'customTourDiscountDetails.*', 'customTourPaymentDetails.*')
        ->paginate($request->perPage == null ? 10 : $request->perPage);

        if($confirmCustom->isEmpty()){
            $confirmCustomArray = [];
        }
        $myObj = new \stdClass();
        foreach ($confirmCustom as $key => $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId ;
                $myObj->groupName = $value->groupName;
                $myObj->contactName = $value->contactName;
                $myObj->contact = $value->contact;
                $myObj->tourPrice = $value->tourPrice;
                $myObj->additionalDis = $value->additionalDis;
                $myObj->discountPrice = $value->discountPrice;
                $myObj->gst = $value->gst;
                $myObj->tcs = $value->tcs;
                $myObj->grandTotal = $value->grandTotal;
                $myObj->advancePayment = $value->advancePayment;
                $myObj->balance = $value->balance;
                $myObj->dueDate = "test";
                $myObj->customPayDetailId = $value->customPayDetailId ;

                $confirmCustomArray[] = $myObj;
                $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $confirmCustomArray,
            'total' => $confirmCustom->total(),
            'currentPage' => $confirmCustom->currentPage(),
            'perPage' => $confirmCustom->perPage(),
            'nextPageUrl' => $confirmCustom->nextPageUrl(),
            'previousPageUrl' => $confirmCustom->previousPageUrl(),
            'lastPage' => $confirmCustom->lastPage()
        ), 200);
    }

    //update pay status from 0 to 1
    public function updatePayStatusCT(Request $request){
        $validateData = Validator::make($request->all(), [
            'customPayDetailId' => 'required|numeric'
        ]);
        if($validateData->fails()){
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }
        //checks toke
        $tokenData = CommonController::checkToken($request->header('token'), [4]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

       try{
         //update status from 0 to 1
         $updateStatus = DB::table('customTourPaymentDetails')->where('customPayDetailId', $request->customPayDetailId )->update([
            'status' => 1
        ]);
        if($updateStatus){
            return response()->json(['message' => "Status updated successfully"], 200);
        }else{
            return response()->json(['message' => "Something went wrong"], 500);
        }
       }catch(\Exception $e){
        return response()->json(['message' => $e->getMessage()], 400);
       }
    }

    //view new payment received
    public function viewNewPayCT(Request $request){
        $validateData = Validator::make($request->all(), [
            'enquiryDetailCustomId' => 'required'
        ]);
        if($validateData->fails()){
            return response(["message" => $validateData->errors()->all()], 400);
        }
        $paymentDetails = DB::table('customTourPaymentDetails') 
            ->join('dropdownpaymentmode', 'customTourPaymentDetails.paymentModeId' ,'=', 'dropdownpaymentmode.paymentModeId')   
            ->where('customTourPaymentDetails.status', 0)
            ->where('customTourPaymentDetails.enquiryDetailCustomId', $request->enquiryDetailCustomId)
            ->select('customTourPaymentDetails.advancePayment', 'dropdownpaymentmode.paymentModeName', 'customTourPaymentDetails.bankName',
            'customTourPaymentDetails.chequeNo', 'customTourPaymentDetails.payDate', 'customTourPaymentDetails.transactionId',
            'customTourPaymentDetails.transactionProof')
            ->get();
            
            // dd($paymentDetails);
                return response()->json(array(
                    'data' => $paymentDetails,
                ), 200);
    }

   //view receipt
   public function viewReceiptCT(Request $request){
    $validateData = Validator::make($request->all(), [
        'customPayDetailId' => 'required'
    ]);
    if($validateData->fails()){
        return response()->json([
            'message' => $validateData->errors()->all()
        ], 400);
    }


    $payDetails = DB::table('customTourPaymentDetails')
    ->join('customTourDiscountDetails', 'customTourPaymentDetails.customDisId' , '=', 'customTourDiscountDetails.customDisId')
    ->where('customTourPaymentDetails.customPayDetailId', $request->customPayDetailId)
    ->where('customTourPaymentDetails.status', 1)
    ->first();
    // dd($payDetails);
    if($payDetails){
        return response()->json([
            'billingName' => $payDetails->billingName,
            'address' => $payDetails->address,
            'phoneNo' => $payDetails->phoneNo,
            'gstIn' => $payDetails->gstIn,
            'panNo' => $payDetails->panNo,
            'tourPrice' => $payDetails->tourPrice,
            'additionalDis' => $payDetails->additionalDis,
            'discountPrice' => $payDetails->discountPrice,
            'gst' => $payDetails->gst,
            'tcs' => $payDetails->tcs,
            'grandTotal' => $payDetails->grandTotal,
            'amount' => $payDetails->advancePayment,
            'balance' => $payDetails->balance,
            'bankName' => $payDetails->bankName,
            'chequeNo' => $payDetails->chequeNo,
            'paymentDate' => $payDetails->payDate,
            'transactionId' => $payDetails->transactionId,
            'status' => $payDetails->status
            
        ]);
    }
   }

   //pending pay

}
