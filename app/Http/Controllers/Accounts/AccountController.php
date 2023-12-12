<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Http\Controllers\CommonController;

class AccountController extends Controller
{
    //account login
    public function accountLogin(Request $request){
        $validateData = Validator::make($request->all(),[
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);
        if($validateData->fails()){
            return  response()->json(array(
                'message' => $validateData->errors()->all()
            ), 400);
        }
        $accounts = User::where(['email' => $request->email, 'password' => $request->password])->first();
        if (!$accounts) {
            return  response()->json(array(
                'message' => 'Accounts Not Found'
            ), 404);
        }  
        //check role id
        if ($accounts->roleId == 4) {

            $token = rand(100000, 999999) . Carbon::now()->timestamp;
            // dd($token);
            $updateToken = DB::table('users')->where('userId', $accounts->userId)->update([
                'token' => $token,
            ]);
            return response()->json([
                'message' => 'Accounts logged in successfully',
                'token' => $token,
                'roleId' => $accounts->roleId
            ], 200);
        } else {
            return response(["message" => "Invalid roleid"], 404);
        }
    }

    
    //previous(confirmed) payments i.e. status = 1 listing
    public function confirmPayList(Request $request){
        $confirmPayList = DB::table('enquirygrouptours')
        ->join('grouptours', 'enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
        ->join('grouptourdiscountdetails', 'enquirygrouptours.enquiryGroupId', '=', 'grouptourdiscountdetails.enquiryGroupId')
        ->join('grouptourpaymentdetails', 'enquirygrouptours.enquiryGroupId', '=', 'grouptourpaymentdetails.enquiryGroupId')
        ->where('grouptourpaymentdetails.status', 1)
        ->select('grouptours.tourName','grouptours.startDate','grouptours.endDate','enquirygrouptours.enquiryGroupId' ,'enquirygrouptours.guestName', 'enquirygrouptours.contact', 'grouptourdiscountdetails.*', 'grouptourpaymentdetails.*')
        ->orderBy('grouptourpaymentdetails.created_at', 'desc')
        ->paginate($request->perPage == null ? 10 : $request->perPage);
        // dd($pendingList);
        if($confirmPayList->isEmpty()){
            $confirmArray = [];
        }
        $myObj = new \stdClass();
        foreach ($confirmPayList as $key => $value) {
            $myObj->enquiryGroupId = $value->enquiryGroupId;
            $myObj->enqDate = date('d-m-Y', strtotime($value->created_at));
            $myObj->tourName = $value->tourName;
            $myObj->startDate = date('d-m-Y', strtotime($value->startDate));
            $myObj->endDate = date('d-m-Y', strtotime($value->endDate));
            $myObj->guestName = $value->guestName;
            $myObj->contact = $value->contact;
            $myObj->tourPrice = $value->tourPrice;
            $myObj->discount = $value->additionalDis;
            $myObj->discounted = $value->discountPrice;
            $myObj->gst = $value->gst;
            $myObj->tcs = $value->tcs;
            $myObj->grand = $value->grandTotal;
            $myObj->advancePayment = $value->advancePayment;
            $myObj->balance = $value->balance;
            $myObj->groupPaymentDetailId = $value->groupPaymentDetailId ;
            

            $confirmArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $confirmArray,
            'total' => $confirmPayList->total(),
            'currentPage' => $confirmPayList->currentPage(),
            'perPage' => $confirmPayList->perPage(),
            'nextPageUrl' => $confirmPayList->nextPageUrl(),
            'previousPageUrl' => $confirmPayList->previousPageUrl(),
            'lastPage' => $confirmPayList->lastPage()
        ), 200);
    }
  
    //listing pending payments
    public function payPendingList(Request $request){
        $pendingList = DB::table('enquirygrouptours')
        ->join('grouptours', 'enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
        ->join('grouptourdiscountdetails', 'enquirygrouptours.enquiryGroupId', '=', 'grouptourdiscountdetails.enquiryGroupId')
        ->join('grouptourpaymentdetails', 'enquirygrouptours.enquiryGroupId', '=', 'grouptourpaymentdetails.enquiryGroupId')
        ->where('grouptourpaymentdetails.status', 0)
        ->select('grouptours.tourName','grouptours.startDate','grouptours.endDate','enquirygrouptours.enquiryGroupId' ,'enquirygrouptours.guestName', 'enquirygrouptours.contact', 'grouptourdiscountdetails.*', 'grouptourpaymentdetails.*')
        ->orderBy('grouptourpaymentdetails.created_at', 'desc')
        ->paginate($request->perPage == null ? 10 : $request->perPage);
        // dd($pendingList);
        if($pendingList->isEmpty()){
            $pendingListArray = [];
        }
        $myObj = new \stdClass();
        foreach ($pendingList as $key => $value) {
            $myObj->enquiryGroupId = $value->enquiryGroupId;
            $myObj->enqDate = date('d-m-Y', strtotime($value->created_at));
            $myObj->tourName = $value->tourName;
            $myObj->startDate = date('d-m-Y', strtotime($value->startDate));
            $myObj->endDate = date('d-m-Y', strtotime($value->endDate));
            $myObj->guestName = $value->guestName;
            $myObj->contact = $value->contact;
            $myObj->tourPrice = $value->tourPrice;
            $myObj->discount = $value->additionalDis;
            $myObj->discounted = $value->discountPrice;
            $myObj->gst = $value->gst;
            $myObj->tcs = $value->tcs;
            $myObj->grand = $value->grandTotal;
            $myObj->advancePayment = $value->advancePayment;
            $myObj->balance = $value->balance;
          
            $myObj->groupPaymentDetailId = $value->groupPaymentDetailId ;
            

            $pendingListArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $pendingListArray,
            'total' => $pendingList->total(),
            'currentPage' => $pendingList->currentPage(),
            'perPage' => $pendingList->perPage(),
            'nextPageUrl' => $pendingList->nextPageUrl(),
            'previousPageUrl' => $pendingList->previousPageUrl(),
            'lastPage' => $pendingList->lastPage()
        ), 200);
    }

    //update the status from 0 to 1
    public function updatePayStatus(Request $request){
        $validateData = Validator::make($request->all(), [
            'groupPaymentDetailId' => 'required|numeric'
        ]);
        if($validateData->fails()){
            return response()->json(array(
                'message' => $validateData->errors()->all()
            ), 400);
        }
        //checks token 
        $tokenData = CommonController::checkToken($request->header('token'), [4]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        try{
            //update status from 0 to 1
        $updateStatus = DB::table('grouptourpaymentdetails')->where('groupPaymentDetailId', $request->groupPaymentDetailId)->update([
            'status' => "1"
        ]);
        if($updateStatus){
            return response()->json(['message' => "Status updated successfully"], 200);
        }else{
            return response()->json(['message' => "Already updated"], 500);
        }
        }catch(\Exception $e){
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    //view new payments received data 
    // public function viewNewPay(Request $request){
    //     $validateData = Validator::make($request->all(), [
    //         'enquiryGroupId' => 'required'
    //     ]);
    //     if($validateData->fails()){
    //         return response()->json(['message' => $validateData->errors()->all()], 400);
    //     }

    //     $newPays = DB::table('enquirygrouptours')
    //         ->join('grouptourdiscountdetails', 'enquirygrouptours.enquiryGroupId', '=', 'grouptourdiscountdetails.enquiryGroupId')
    //         ->join('grouptourpaymentdetails', function ($join) {
    //             $join->on('enquirygrouptours.enquiryGroupId', '=', 'grouptourpaymentdetails.enquiryGroupId')
    //                 ->where('grouptourpaymentdetails.status', '=', 0)
    //                 ->whereRaw('grouptourpaymentdetails.created_at = (SELECT MAX(created_at) FROM grouptourpaymentdetails WHERE enquiryGroupId = grouptourpaymentdetails.enquiryGroupId)');
    //         })
    //         ->where('grouptourpaymentdetails.enquiryGroupId', $request->enquiryGroupId)
    //         ->select('grouptourpaymentdetails.groupPaymentDetailId','grouptourpaymentdetails.advancePayment','grouptourpaymentdetails.bankName', 'grouptourpaymentdetails.balance', 'grouptourpaymentdetails.status', 'grouptourdiscountdetails.billingName', 'grouptourdiscountdetails.additionalDis','grouptourdiscountdetails.discountPrice')
    //         ->get();

    //     // dd($newPays);
    //    return response()->json(['data' => $newPays], 200);
    // }


    //pending payment received 
      public function viewNewPay(Request $request){
        $validateData = Validator::make($request->all(),[
            'enquiryGroupId' => "required|numeric"
        ]);
        if($validateData->fails()){
            return response(["message" => $validateData->errors()->all()], 400);
        }
          //cheks token 
          $tokenData = CommonController::checkToken($request->header('token'), [4]);//sales,account
          if (!$tokenData) {
              return response()->json(['message' => 'Invalid Token'], 408);
          }

        $paymentDetails = DB::table('grouptourpaymentdetails') 
            ->join('dropdownpaymentmode', 'grouptourpaymentdetails.paymentModeId' ,'=', 'dropdownpaymentmode.paymentModeId')   
            ->where('grouptourpaymentdetails.status', 0)
            ->where('grouptourpaymentdetails.enquiryGroupId', $request->enquiryGroupId)
            ->select('grouptourpaymentdetails.advancePayment', 'dropdownpaymentmode.paymentModeName', 'grouptourpaymentdetails.bankName',
            'grouptourpaymentdetails.chequeNo', 'grouptourpaymentdetails.paymentDate', 'grouptourpaymentdetails.transactionId',
            'grouptourpaymentdetails.transactionProof')
            ->get();
            
            // dd($paymentDetails);
                return response()->json(array(
                    'data' => $paymentDetails,
                ), 200);
    }

    //view details perticular id
    public function viewNewPayDetails(Request $request){
        $validateData = Validator::make($request->all(), [
            'groupPaymentDetailId' => 'required'
        ]);
        if($validateData->fails()){
            return response()->json([
                'message' => $validateData->errors()->all()
            ], 400);
        }
    
         //cheks token 
         $tokenData = CommonController::checkToken($request->header('token'), [4]);//account
         if (!$tokenData) {
             return response()->json(['message' => 'Invalid Token'], 408);
        } 
        $payDetails = DB::table('grouptourpaymentdetails')
        ->join('dropdownpaymentmode', 'grouptourpaymentdetails.paymentModeId' , '=', 'dropdownpaymentmode.paymentModeId')
        ->where('grouptourpaymentdetails.groupPaymentDetailId', $request->groupPaymentDetailId)
        ->where('status', 0)
        ->first();
        
        if($payDetails){
            return response()->json([
                'advancePayment' => $payDetails->advancePayment,
                'bankName' => $payDetails->bankName,
                'paymentMode' => $payDetails->paymentModeName ,
                'chequeNo' => $payDetails->chequeNo,
                'paymentDate' => $payDetails->paymentDate,
                'transactionId' => $payDetails->transactionId,
                'transactionProof' => $payDetails->transactionProof,
                'status' => $payDetails->status
                
            ]);
        }else{
            return response()->json(['message' => 'payment is confirmed'], 500);
        }
    }

    //view receipt confirm payments
    public function viewReceipt(Request $request){
        $validateData = Validator::make($request->all(), [
            'groupPaymentDetailId' => 'required',
            'enquiryGroupId' => 'required'
        ]);
        if($validateData->fails()){
            return response()->json([
                'message' => $validateData->errors()->all()
            ], 400);
        }
    
         //cheks token 
         $tokenData = CommonController::checkToken($request->header('token'), [4]);//account
         if (!$tokenData) {
             return response()->json(['message' => 'Invalid Token'], 408);
        }
        // dd($advancePayment);
        $payDetails = DB::table('grouptourpaymentdetails')
        ->join('grouptourdiscountdetails', 'grouptourpaymentdetails.enquiryGroupId' , '=', 'grouptourdiscountdetails.enquiryGroupId')
        ->join('enquirygrouptours', 'grouptourpaymentdetails.enquiryGroupId', '=', 'enquirygrouptours.enquiryGroupId')
        ->join('grouptours', 'grouptours.groupTourId', '=', 'grouptourpaymentdetails.groupTourId') 
        ->join('dropdownpaymentmode', 'grouptourpaymentdetails.paymentModeId', '=', 'dropdownpaymentmode.paymentModeId')
        ->where('grouptourpaymentdetails.groupPaymentDetailId', $request->groupPaymentDetailId)
        ->where('grouptourpaymentdetails.status', 1)
        ->first();
     
       
        if($payDetails){
            $totalTourPricePrice = $payDetails->tourPrice + $payDetails->gst + $payDetails->tcs - $payDetails->additionalDis ;
    //    dd($totalTourPricePrice);
       $alreadyPaid = DB::table('grouptourpaymentdetails')
            ->where('enquiryGroupId', $request->enquiryGroupId)
            ->where('status', 1)
            ->where('groupPaymentDetailId', '!=', $request->groupPaymentDetailId)
            ->sum('advancePayment');
        // dd($alreadyPaid);
        $remainingAmount = $totalTourPricePrice -  $alreadyPaid - $payDetails->advancePayment;
        // dd($remainingAmount);
        $destination = ($payDetails->destinationId == 1) ? 'domestic' : 'international';

            return response()->json([
                'billingName' => $payDetails->billingName,
                'address' => $payDetails->address,
                'phoneNo' => $payDetails->phoneNo,
                'gstin' => $payDetails->gstin,
                'panNo' => $payDetails->panNo,
                'tourName' => $payDetails->tourName,
                'destination' => $destination,
                'days' => $payDetails->days,
                'night' => $payDetails->night,
                'adults' => $payDetails->adults,
                'child' => $payDetails->child,
                'tourPrice' => $payDetails->tourPrice,
                'totalTourPrice' => $totalTourPricePrice,
                'alreadyPaid' => $alreadyPaid,
                'remainingAmount' => $remainingAmount,
                'advancePayment' => $payDetails->advancePayment,
                'bankName' => $payDetails->bankName,
                'chequeNo' => $payDetails->chequeNo,
                'paymentDate' => $payDetails->paymentDate,
                'paymentMode' => $payDetails->paymentModeName,
                'transactionId' => $payDetails->transactionId,
                'status' => $payDetails->status,
            ]);
        }else{
            return response()->json(['message' => 'payment is not  confirmed yet'], 500);
        }
    }

    //pending payments for card purchase 
    public function pendingCardPurchase(Request $request){
        //checks token 
        $tokenData = CommonController::checkToken($request->header('token'), [4]);//account
         if (!$tokenData) {
             return response()->json(['message' => 'Invalid Token'], 408);
        }
        //list of pending card purchase
        $pendingPays = DB::table('cardPurchase')
            ->where('status', 0)
            ->paginate($request->perPage == null ? 10: $request->perPage);
        if($pendingPays->isEmpty()){
            $pendingArrays = [];
        }
        $myObj = new \stdClass();
        foreach ($pendingPays as $key => $value) {
            $myObj->loyalGuestId = $value->loyalGuestId ;
            $myObj->guestTypeId = $value->guestTypeId;
            $myObj->guestInfoId = $value->guestInfoId;
            $myObj->phone = $value->phone;
            $myObj->cardId = $value->cardId;
            $myObj->total = $value->total;
            $myObj->paymentType = $value->paymentTypeId;
            $myObj->guestCardId = $value->guestCardId;
            $myObj->status = $value->status;

            $pendingArrays[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $pendingArrays,
            'total' => $pendingPays->total(),
            'currentPage' => $pendingPays->currentPage(),
            'perPage' => $pendingPays->perPage(),
            'nextPageUrl' => $pendingPays->nextPageUrl(),
            'previousPageUrl' => $pendingPays->previousPageUrl(),
            'lastPage' => $pendingPays->lastPage()
        ), 200);
    }

    //update card pays 
    public function updateCardPays(Request $request){
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [4]);//account
         if (!$tokenData) {
             return response()->json(['message' => 'Invalid Token'], 408);
        }

        //update status from 0 to 1
        $updatePays = DB::table('loyaltyGuest')->where('loyalGuestId', $request->loyalGuestId)->update([
            'status' => 1
        ]);

        if($updatePays){
            return response()->json(['message' => 'Status updated successfully'], 200);
        }else{
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }
  
    //accounts dashboard 
    public function accountDashboard(Request $request){
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [4]);//account
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
       }

       //total billing
       $totalGtBill = DB::table('grouptourpaymentdetails')->count();
    //    dd($totalGtBill);
       $billApproveGt = DB::table('grouptourpaymentdetails')
                ->where('status', 1)
                ->count();
        // dd($billApproveGt);
        $billPendingGt = DB::table('grouptourpaymentdetails')
                ->where('status', 0)
                ->count();
        // dd($billPendingGt);
        $totalCtBill = DB::table('customTourPaymentDetails')->count();
        // dd($totalCtBill);
        $billApproveCt = DB::table('customTourPaymentDetails')
                ->where('status', 1)
                ->count();
        // dd($billApproveCt);
        $billPendingCt =  DB::table('customTourPaymentDetails')
                ->where('status', 0)
                ->count();
        // dd($billPendingCt);

        $totalBill = $totalGtBill + $totalCtBill;
        
        $totalbillApprove = $billApproveGt + $billApproveCt;
        
        $totalBillPending = $billPendingGt + $billPendingCt;
       
        return response()->json([
            'totalBill' => $totalBill,
            'totalbillApprove' => $totalbillApprove,
            'totalBillPending' => $totalBillPending
        ]);

        
    }
}
