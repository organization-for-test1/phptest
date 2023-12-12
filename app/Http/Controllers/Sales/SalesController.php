<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Http\Controllers\CommonController;

class SalesController extends Controller
{
    //sales login
     public function salesLogin(Request $request){
        $validateData = Validator::make($request->all(),[
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);
        if($validateData->fails()){
            return  response()->json(array(
                'message' => $validateData->errors()->all()
            ), 400);
        }
        $sales =DB::table('users')->where('email', $request->email)->first();
        // dd($sales);
        if (!$sales) {
            return  response()->json(array(
                'message' => 'Invalid email'
            ), 422);
        } 
        //check password
        if (!password_verify($request->password, $sales->password)) {
            return response(['message' => 'Invalid password'], 422);
        }
        //check role id
        if ($sales->roleId == 2) {
            $token = rand(100000, 999999) . Carbon::now()->timestamp;
            $updateToken = DB::table('users')->where('userId', $sales->userId)->update([
                'token' => $token,
            ]);
            return response()->json([
                'message' => 'Sales logged in successfully',
                'token' => $token,
                'roleId' => $sales->roleId
            ], 200);
        } else {
            return response(["message" => "Invalid roleid"], 404);
        }
    }  
   
    //guests details group
    public function groupGuestInfo(Request $request){
        $groupGuest = DB::table('enquirygrouptours')
        ->select('enquirygrouptours.guestName', 'groupguestdetails.dob', 'groupguestdetails.marriageDate', 'enquirygrouptours.contact', 'enquirygrouptours.mail', 'groupguestdetails.gender')
        ->join('grouptourguestdetails', 'enquirygrouptours.enquiryGroupId', '=', 'groupguestdetails.enquiryGroupId')
        ->paginate($request->perPage == null ? 10 : $request->perPage);
        // dd($groupGuest);

            if($groupGuest->isEmpty()){
                $groupGuestArray = [];
            }
            $myObj = new \stdClass();
            foreach ($groupGuest as  $value) {
                $myObj->guestName = $value->guestName;
                $myObj->contact = $value->contact;
                $myObj->mail = $value->mail;
                $myObj->dob = $value->dob;
                $myObj->marriageDate = $value->marriageDate;

                $groupGuestArray[] = $myObj;
                $myObj = new \stdClass();
            }
            return  response()->json(array(
                'data' => $groupGuestArray,
                'total' => $groupGuest->total(),
                'currentPage' => $groupGuest->currentPage(),
                'perPage' => $groupGuest->perPage(),
                'nextPageUrl' => $groupGuest->nextPageUrl(),
                'previousPageUrl' => $groupGuest->previousPageUrl(),
                'lastPage' => $groupGuest->lastPage()
            ), 200);
    }

   

    //guest information both custom and grouptours
    public function guestInformation(Request $request){
        //checks the token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $guestDetails = DB::table('grouptourguestdetails')
            // ->join('grouptourdiscountdetails', 'grouptourguestdetails.enquiryGroupId', '=', 'grouptourdiscountdetails.enquiryGroupId')
            ->where('grouptourguestdetails.createdBy', $tokenData->userId)
            ->paginate();
        // dd($guestDetails);
        if($guestDetails->isEmpty()){
            $guestArray = [];
        }
        $myObj = new \stdClass();
        foreach ($guestDetails as  $value) {
            $myObj->groupGuestDetailId = $value->groupGuestDetailId;
            $myObj->familyHeadName = $value->familyHeadName;
            $myObj->contact = $value->contact;
            // $myObj->tourPrice = $value->tourPrice;

            $guestArray[] = $myObj;
            $myObj = new \stdClass();

        }
        return response()->json([
            'data' => $guestArray
        ], 200);
    }
    
    //guest details
    public function guestDetails(Request $request){
        $validateData = Validator::make($request->all(), [
            'groupGuestDetailId' => 'required|numeric'
        ]);
        if($validateData->fails()){
            return  response()->json(array(
                'message' => $validateData->errors()->all()
            ), 400);
        }

        $guests = DB::table('grouptourguestdetails')
            ->where('groupGuestDetailId', $request->groupGuestDetailId)
            ->first();
      
        if($guests){
            return response()->json([
                'familyHeadName' => $guests->familyHeadName,
                'gender' => $guests->gender,
                'contact' => $guests->contact,
                'address' => $guests->address,
                'mailId' => $guests->mailId,
                'dob' => $guests->dob,
                'marriageDate' => $guests->marriageDate,
                'adharCard' => $guests->adharCard,
                'passport' => $guests->passport
            ]);
        }
    }


    //dropdown guest type
    // public function dropdownGuestType(){
    //     $guestType = DB::table('dropdownGuestType')->paginate();
    //     if($guestType->isEmpty()){
    //         $guestArray = [];
    //     }
    //     $myObj = new \stdClass();
    //     foreach ($guestType as  $value) {
    //         $myObj->guestTypeId = $value->guestTypeId ;
    //         $myObj->guestTypeName = $value->guestTypeName;

    //         $guestArray[] = $myObj;
    //         $myObj = new \stdClass();
    //     }
    //     return response()->json([
    //         'data' => $guestArray
    //     ], 200);
    // }

    //dropdown card type
    public function dropdownCardType(){
        $cardType = DB::table('cardType')->paginate();
        if($cardType->isEmpty()){
            $cardTypeArray = [];
        }
        $myObj = new \stdClass();
        foreach ($cardType as  $value) {
            $myObj->cardId  = $value->cardId  ;
            $myObj->cardName = $value->cardName;
            $myObj->cardPrice = $value->cardPrice;


            $cardTypeArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'data' => $cardTypeArray
        ], 200);
    }

    //guests name dropdown from guestDetailsTable
    public function guestNamesDropdown(Request $request){
        $guestNames = DB::table('grouptourguestdetails')->get();
        $guestNamesArrayGt = [];
        foreach ($guestNames as  $value) {
            $myObj = new \stdClass();
            $myObj->groupGuestDetailId = $value->groupGuestDetailId ;
            $myObj->familyHeadName = $value->familyHeadName ;
            $myObj->guestId = $value->guestId;

            $guestNamesArrayGt[] = $myObj;
        }
        //custom guest details
        $customTourGuestNames = DB::table('customTourGuestDetails')->get();
        $guestNamesArrayCt = [];
        foreach ($customTourGuestNames as  $value) {
            $myObj = new \stdClass();
            $myObj->customGuestDetailsId  = $value->customGuestDetailsId  ;
            $myObj->familyHeadName = $value->familyHeadName ;
            $myObj->guestId = $value->guestId;

            $guestNamesArrayCt[] = $myObj;
        }
        $combinedData = [
            'grouptourguestdetails' => $guestNamesArrayGt,
            'customTourGuestDetails' => $guestNamesArrayCt,
        ];
    
        return response()->json(['data' => $combinedData], 200);
     
    }

    //guest details
    public function guestsInfo(Request $request){
        if($request->groupGuestDetailId && $request->guestId){
            $groupTours = DB::table('grouptourguestdetails')
                ->where('groupGuestDetailId', $request->groupGuestDetailId)
                ->where('guestId', $request->guestId)
                ->first();
            if( $groupTours){
                return response()->json([
                    'address' => $groupTours->address,
                    'contact' => $groupTours->contact,
                ]);
            }
        }elseif($request->customGuestDetailsId && $request->guestId){
            $groupTours = DB::table('grouptourguestdetails')
            ->where('customGuestDetailsId', $request->customGuestDetailsId)
            ->where('guestId', $request->guestId)
            ->first();
        if( $groupTours){
            return response()->json([
                'address' => $groupTours->address,
                'contact' => $groupTours->contact,
            ]);
        }
        }
    }
    //loyalty program 
    public function cardPurchase(Request $request){
        //checks token 
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $guestId = $request->guestId;
        if($request->cardId == 1){
            $guestCardId = 'G-' . $guestId;
        }elseif($request->cardId == 2){
            $guestCardId = 'S-' . $guestId;
        }
        $loyalDetails = DB::table('cardPurchase')->insert([
            'guestTypeId' => $request->guestTypeId,
            'guestInfoId' => $request->guestInfoId,
            'phone' => $request->phone,
            'address' => $request->address,
            'cardId' => $request->cardId,
            'price' => $request->price,
            'gst' => $request->gst,
            'total' => $request->total,
            'paymentType' => $request->paymentType,
            'transactionId' => $request->transactionId,
            'guestCardId' => $guestCardId,
            'createdBy' => $tokenData->userId
        ]);
        if($loyalDetails){
            return response()->json(['message' => 'Loyal Guest Created Successfully' ], 200);
        }else{
            return response()->json(['message' => 'Something went wrong' ], 500);
            
        }
    }

    //guest list with search in loyalty program
    public function loyalGuestsLists(Request $request){
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $guests = DB::table('loyaltyGuest')->where('createdBy', $tokenData->createdBy)->get();

          //searching
          $guests->where(function ($q) use ($request) {
            if (!empty($request->guestName)) {
                $q->orWhere('guestName', 'like', '%' . $request->guestName . '%');
            }
            if (!empty($request->guestRefId)) {
                $q->orWhere('guestRefId', 'like', '%' . $request->guestRefId . '%');
            }            
        });
        
        //pagination
        $guestsDetails = $guests->paginate($request->perPage == null ? 10 : $request->perPage);
        if($guestsDetails->isEmpty()){
            $guestsArray = [];
        }
        $myObj = new \stdClass();
        foreach ($guestsDetails as $key => $value) {
            $myObj->loyalGuestId = $value->loyalGuestId;
            $myObj->guestName = $value->guestName;
            $myObj->guestRefId = $value->guestRefId;
            $myObj->points = $value->points;

            $guestsArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $guestsArray,
            'total' => $guestsDetails->total(),
            'currentPage' => $guestsDetails->currentPage(),
            'perPage' => $guestsDetails->perPage(),
            'nextPageUrl' => $guestsDetails->nextPageUrl(),
            'previousPageUrl' => $guestsDetails->previousPageUrl(),
            'lastPage' => $guestsDetails->lastPage()
        ), 200);
    }

    //loyalGuestView
    public function loyalGuestView(Request $request){
        //validate
        $validateData = Validator::make($request->all(), [
            'loyalGuestId' => 'required|numeric'
        ]);
        if($validateData->fails()){
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }
        $guests = DB::table('')
            ->where('loyalGuestId', $request->loyalGuestId)
            ->get();
        if($guests){
            return response()->json([
                'referralId' => $guests->$guests,
            ]);
        }
    }

    //sales 

}
