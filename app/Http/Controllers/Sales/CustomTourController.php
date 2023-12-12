<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CommonController;
use Carbon\Carbon;
use Exception;

class CustomTourController extends Controller
{
    //dropdown hotel category
    public function ddHotelCat(Request $request)
    {
        $hotlCatList = DB::table('dropdownHotelCategory')->paginate();
        // dd($groupTours);
        if ($hotlCatList->isEmpty()) {
            $hotlCatList_array = [];
        }
        $myObj = new \stdClass();
        foreach ($hotlCatList as $key => $value) {
            $myObj->hotelCatId   = $value->hotelCatId;
            $myObj->hotelCatName = $value->hotelCatName;

            $hotlCatList_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $hotlCatList_array,
        ), 200);
    }


    //enquiry form custom tour controller
    public function enquiryCustomTour(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'groupName' => 'required|string|max:20',
            'contactName' => 'required|string|max:20',
            'destinationId' => 'required|numeric',
            'contact' => 'required|digits:10',
            'startDate' => 'required',
            'endDate' => 'required',
            'nights' => 'required|numeric',
            'days' => 'required|numeric',
            'hotelCatId' => 'required|numeric',
            'adults' => 'required|numeric',
            'rooms' => 'required',
            'extraBed' => 'required|numeric',
            'mealPlanId' => 'required|numeric',
            'familyHeadNo' => 'required|numeric',
            'nextFollowUp' => 'required',
            'enquiryReferId' => 'required',

        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }
        //checks the token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        //country state
        if ($request->destinationId == 1) {
            $countryState = $request->ctCityId;
        } elseif ($request->destinationId == 2) {
            $countryState = $request->countryId;
        }
        //inserting data in table
        try {
            $namePrefix = substr($request->contactName, 0, 2);
            $guestId = $namePrefix . rand(100000, 999999);

            $customTourDetails = DB::table('enquiryCustomTours')->insert([
                'groupName' => $request->groupName,
                'contactName' => $request->contactName,
                'destinationId' => $request->destinationId,
                'contact' => $request->contact,
                'startDate' => $request->startDate,
                'endDate' => $request->endDate,
                'countryState' => $countryState,
                'nights' => $request->nights,
                'days' => $request->days,
                'nightsNo' => $request->nightsNo,
                'hotelCatId' => $request->hotelCatId,
                'adults' => $request->adults,
                'child' => $request->child,
                'age' => json_encode($request->input('age')),
                'rooms' => $request->rooms,
                'extraBed' => $request->extraBed,
                'mealPlanId' => $request->mealPlanId,
                'familyHeadNo' => $request->familyHeadNo,
                'enquiryReferId' => $request->enquiryReferId,
                'guestRefId' => $request->guestRefId,
                'priorityId' => $request->priorityId,
                'nextFollowUp' => $request->nextFollowUp,
                'notes' => $request->notes,
                'createdBy' => $tokenData->userId
            ]);
            if ($customTourDetails) {
                return response(["message" => "Custom Tour Enquiry Added Successfully"], 200);
            } else {
                return response(["message" => "Something Went Wrong"], 500);
            }
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], 400);
        }
    }

    //cancel ongoin enquiries for custom tour
    public function cancelCustomEnquiry(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'enquiryCustomId' => "required|numeric",
            'closureReason' => "required"
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        // dd($tokenData->createdBy);

        //update the enquiryProcess value
        $updateEnqProcess = DB::table('enquiryCustomTours')
            ->where('enquiryCustomId', $request->enquiryCustomId)
            // ->where('enquiryCustomTours.createdBy', $tokenData->createdBy)
            ->update([
                'enquiryProcess' => 3,
                'closureReason' => $request->closureReason
            ]);
        if ($updateEnqProcess) {
            return response(["message" => "Enquiry Process Deleted Successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }

    //lost enquiries list
    public function lostEnquiryCustomTour(Request $request)
    {
        //cheks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        //listing
        $lostCustomTour = DB::table('enquiryCustomTours')
            ->where('enquiryProcess', 3)
            ->where('createdBy', $tokenData->userId)
            ->paginate($request->PerPage == null ? 10 : $request->perPage);

        if ($lostCustomTour->isEmpty()) {
            $lostCustomArray = [];
        }
        $myObj = new \stdClass();
        foreach ($lostCustomTour as $key => $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId;
            $myObj->enqDate = date('d-m-y', strtotime($value->created_at));
            $myObj->guestName = $value->contactName;
            $myObj->contact = $value->contact;
            $myObj->destination = $value->destinationId;
            $myObj->pax = $value->adults + $value->child;
            $myObj->lastFollow =  $value->nextFollowUp;
            $myObj->closureReason = $value->closureReason;

            $lostCustomArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $lostCustomArray,
            'total' => $lostCustomTour->total(),
            'currentPage' => $lostCustomTour->currentPage(),
            'perPage' => $lostCustomTour->perPage(),
            'nextPageUrl' => $lostCustomTour->nextPageUrl(),
            'previousPageUrl' => $lostCustomTour->previousPageUrl(),
            'lastPage' => $lostCustomTour->lastPage()
        ), 200);
    }

    //edit enquiry custom tour details
    public function updateEnquiryCustomTour(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'enquiryCustomId' => 'required'
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }

        //update the value
        $updateDetailCustom = DB::table('enquiryCustomTours')->where('enquiryCustomId', $request->enquiryCustomId)->update([
            'groupName' => $request->groupName,
            'contactName' => $request->contactName,
            'destinationId' => $request->destinationId,
            'contact' => $request->contact,
            'countryState' => $request->countryState,
            'nights' => $request->nights,
            'days' => $request->days,
            'nightsNo' => $request->nightsNo,
            'hotelCatId' => $request->hotelCatId,
            'adults' => $request->adults,
            'child' => $request->child,
            'age' => $request->age,
            'rooms' => $request->rooms,
            'extraBed' => $request->extraBed,
            'mealPlanId' => $request->mealPlanId,
            'familyHeadNo' => $request->familyHeadNo,
            'enquiryReferId' => $request->enquiryReferId,
            'guestRefId' => $request->guestRefId,
            'priorityId' => $request->priorityId,
            'nextFollowUp' => $request->nextFollowUp
        ]);
        if ($updateDetailCustom) {
            return response(["message" => "Enquiry custom details updated successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }

    //packages pdf listing
    public function packageList(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'enquiryCustomId' => 'required'
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        //packages listing
        $packagesList = DB::table('packagesCustomTour')->where('enquiryCustomId', $request->enquiryCustomId)->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($packagesList->isEmpty()) {
            $packageArray = [];
        }
        $myObj = new \stdClass();
        foreach ($packagesList as $key => $value) {
            $myObj->packageCustomId = $value->packageCustomId;
            $myObj->package = $value->package;
            $myObj->adult = $value->adult;
            $myObj->extraBed = $value->extraBed;
            $myObj->childWithout = $value->childWithout;

            $packageArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $packageArray,
            'total' => $packagesList->total(),
            'currentPage' => $packagesList->currentPage(),
            'perPage' => $packagesList->perPage(),
            'nextPageUrl' => $packagesList->nextPageUrl(),
            'previousPageUrl' => $packagesList->previousPageUrl(),
            'lastPage' => $packagesList->lastPage()
        ), 200);
    }

    //finalize quotation
    //finalize packages
    public function finalPackage(Request $request)
    {
        $validateData = Validator::make($request->All(), [
            'packageCustomId' => 'required',
        ]);
        if ($validateData->fails()) {
            return response()->json(["message" => $validateData->errors()->all()], 400);
        }

        //update isConfirm from 0 to 1
        $isConfirm = DB::table('packagesCustomTour')
            ->where('enquiryCustomId', $request->enquiryCustomId)
            ->where('packageCustomId', $request->packageCustomId)
            ->update([
                'isFinal' => 1
            ]);
        // if($isConfirm){
        return response()->json(["message" => "isConfirm updated successfully"], 200);
        // }else{
        //     return response()->json(["message" => "Something went wrong"], 500);
        // }
    }

    //enquiry follow up  list customize tour
    public function enquiryFollowCustomTourList(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]); //sales,operations
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
            ->where('enquiryCustomTours.createdBy', $tokenData->userId)
            ->select('enquiryCustomTours.*', 'dropdowndestination.destinationName')
            ->orderBy('enquiryCustomTours.enquiryCustomId', 'desc');
        // ->paginate($request->perPage == null ? 10 : $request->perPage);

        if ($request->startDate != '' && $request->endDate != '') {
            $start_datetime = Carbon::parse($request->startDate)->startOfDay();
            $end_datetime = Carbon::parse($request->endDate)->endOfDay();

            $enquiryCustomList->where(function ($query) use ($start_datetime, $end_datetime) {
                $query->where('startDate', '>=', $start_datetime)
                    ->where('endDate', '<=', $end_datetime);
            });
        } elseif ($request->startDate != '') {
            $start_datetime = Carbon::parse($request->startDate)->startOfDay();
            $enquiryCustomList->where('startDate', '>=', $start_datetime);
        } elseif ($request->endDate != '') {
            $end_datetime = Carbon::parse($request->endDate)->endOfDay();
            $enquiryCustomList->where('endDate', '<=', $end_datetime);
        } elseif (!empty($request->search)) {
            $search = $request->search;
            $enquiryCustomList->where('groupName', 'like', '%' . $search . '%');
        } elseif (!empty($request->search)) {
            $search = $request->search;
            $enquiryCustomList->where('contactName', 'like', '%' . $search . '%');
        }

        //pagination
        $enquiryCustomListing = $enquiryCustomList->paginate($request->perPage == null ? 10 : $request->perPage);

        if ($enquiryCustomListing->isEmpty()) {
            $enquiryCustomArray = [];
        }
        $myObj = new \stdClass();
        foreach ($enquiryCustomListing as $key => $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId;
            $myObj->enqDate = date('d-m-y', strtotime($value->created_at));
            $myObj->groupName = $value->groupName;
            $myObj->contactName = $value->contactName;
            $myObj->startDate = date('d-m-y', strtotime($value->startDate));
            $myObj->endDate = date('d-m-y', strtotime($value->endDate));
            $myObj->contact = $value->contact;
            $myObj->destinationName = $value->destinationName;
            $myObj->pax = $value->adults + $value->child;
            $myObj->lastFollowUp = date('d-m-y', strtotime($value->created_at));
            $myObj->nextFollowUp = date('d-m-y', strtotime($value->nextFollowUp));

            $enquiryCustomArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $enquiryCustomArray,
            'total' => $enquiryCustomListing->total(),
            'currentPage' => $enquiryCustomListing->currentPage(),
            'perPage' => $enquiryCustomListing->perPage(),
            'nextPageUrl' => $enquiryCustomListing->nextPageUrl(),
            'previousPageUrl' => $enquiryCustomListing->previousPageUrl(),
            'lastPage' => $enquiryCustomListing->lastPage()
        ), 200);
    }

    //upcoming follow up enquiries custom tour
    public function upcomingenquiryFollowCT(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]); //sales,operations
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $today = now()->toDateString();
        //listing
        $enquiryCustomList = DB::table('enquiryCustomTours')
            ->join('dropdowndestination', 'enquiryCustomTours.destinationId', 'dropdowndestination.destinationId')
            ->where('enquiryCustomTours.enquiryProcess', 1)
            ->where('enquiryCustomTours.nextFollowUp', '>', $today)
            ->where('enquiryCustomTours.createdBy', $tokenData->userId)
            ->select('enquiryCustomTours.*', 'dropdowndestination.destinationName')
            ->orderBy('enquiryCustomTours.enquiryCustomId', 'desc')
            ->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($enquiryCustomList->isEmpty()) {
            $enquiryCustomArray = [];
        }
        $myObj = new \stdClass();
        foreach ($enquiryCustomList as $key => $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId;
            $myObj->enqDate = date('d-m-y', strtotime($value->created_at));
            $myObj->groupName = $value->groupName;
            $myObj->contactName = $value->contactName;
            $myObj->startDate = date('d-m-y', strtotime($value->startDate));
            $myObj->endDate = date('d-m-y', strtotime($value->endDate));
            $myObj->contact = $value->contact;
            $myObj->destinationName = $value->destinationName;
            $myObj->pax = $value->adults + $value->child;
            $myObj->lastFollowUp = date('d-m-y', strtotime($value->created_at));
            $myObj->nextFollowUp = date('d-m-y', strtotime($value->nextFollowUp));

            $enquiryCustomArray[] = $myObj;
            $myObj = new \stdClass();
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

    //update next follow up date
    public function updateNextFollowUp(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'enquiryCustomId' => 'required|numeric',
            'nextFollowUp' => 'required',
            'remark' => 'required'
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }

        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        //update the nextFollowUp date
        $nextFollow = DB::table('enquiryCustomTours')
            ->where('enquiryCustomId', $request->enquiryCustomId)
            ->where('enquiryCustomTours.createdBy', $tokenData->userId)
            ->update([
                'nextFollowUp' => $request->nextFollowUp,
                'remark' => $request->remark,
            ]);
        if ($nextFollow) {
            return response(["message" => "Next Follow Up updated successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }
    //enquiry custom details
    public function enquiryCt(Request $request)
    {
        //checks the toekn
        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]); //sales,operations
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        //enquiry details
        $enqDetails = DB::table('enquiryCustomTours')
            ->join('dropdowndestination', 'enquiryCustomTours.destinationId', '=', 'dropdowndestination.destinationId')
            ->join('dropdownenquiryreference', 'enquiryCustomTours.enquiryReferId', '=', 'dropdownenquiryreference.enquiryReferId')
            ->join('dropdownpriority', 'enquiryCustomTours.priorityId', '=', 'dropdownpriority.priorityId')
            ->where('enquiryCustomId', $request->enquiryCustomId)->first();
        if ($enqDetails) {
            return response()->json([
                'groupName' => $enqDetails->groupName,
                'contactName' => $enqDetails->contactName,
                'destinationName' => $enqDetails->destinationName,
                'contact' => $enqDetails->contact,
                'nights' => $enqDetails->nights,
                'days' => $enqDetails->days,
                'adults' => $enqDetails->adults,
                'child' => $enqDetails->child,
                'age' => $enqDetails->age,
                'enquiryReferName' => $enqDetails->enquiryReferName,
                'guestRefId' => $enqDetails->guestRefId,
                'priorityName' => $enqDetails->priorityName,
                'familyHeadNo' => $enqDetails->familyHeadNo,
                'guestRef' => $enqDetails->guestRefId
            ], 200);
        }
    }

    //enquiry custom tour details booking form - 1
    public function enquiryCustomTourDetails(Request $request)
    {
        //checks token
        $validateData = Validator::make($request->all(), [
            'enquiryCustomId' => 'required',
            'familyHeadName' => 'required',
            'paxPerHead' => 'required',
        ]);
        if ($validateData->fails()) {
            return response()->json(["message" => $validateData->erros()->all()], 400);
        }

        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $namePrefix = substr($request->familyHeadName, 0, 2);
        $guestId = $namePrefix . rand(100000, 999999);
        // dd($guestId);
        //inserting data in table
        $enquiryCustomDetail = DB::table('customTourEnquiryDetails')->insertGetId([
            'enquiryCustomId' => $request->enquiryCustomId,
            'familyHeadName' => $request->familyHeadName,
            'paxPerHead' => $request->paxPerHead,
            'guestId' => $guestId
        ]);
        // dd( $enquiryCustomDetail);
        if ($enquiryCustomDetail) {
            return response()->json([
                "message" => 'Custom Tour Details  added successfully',
                'enquiryDetailCustomId' => $enquiryCustomDetail,
                'guestId' => $guestId
            ], 200);
        }
        return response()->json(["message" => "Something went wrong"], 500);
    }

    //family head listing
    public function familyHeadList(Request $request)
    {
        //checks token
        $validateData = Validator::make($request->all(), [
            'enquiryCustomId' => 'required',
        ]);
        if ($validateData->fails()) {
            return response()->json(["message" => $validateData->erros()->all()], 400);
        }

        $familyHeadList = DB::table('customTourEnquiryDetails')
            ->where('enquiryCustomId', $request->enquiryCustomId)
            ->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($familyHeadList->isEmpty()) {
            $familyListArray = [];
        }
        $myObj = new \stdClass();
        foreach ($familyHeadList as $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId;
            $myObj->enquiryDetailCustomId = $value->enquiryDetailCustomId;
            $myObj->familyHeadName = $value->familyHeadName;
            $myObj->paxPerHead = $value->paxPerHead;

            $familyListArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $familyListArray,
            'total' => $familyHeadList->total(),
            'currentPage' => $familyHeadList->currentPage(),
            'perPage' => $familyHeadList->perPage(),
            'nextPageUrl' => $familyHeadList->nextPageUrl(),
            'previousPageUrl' => $familyHeadList->previousPageUrl(),
            'lastPage' => $familyHeadList->lastPage()
        ), 200);
    }

    //dropdowm rooms packages
    public function dropdownRoomPackages(Request $request)
    {
        $roomsPackages = DB::table('packagesCustomTour')
            ->where('enquiryCustomId', $request->enquiryCustomId)
            ->where('isFinal', 1)
            ->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($roomsPackages->isEmpty()) {
            $roomsArray = [];
        }
        $myObj = new \stdClass();
        foreach ($roomsPackages as $key => $value) {
            $myObj->packageCustomId = $value->packageCustomId;
            $myObj->enquiryCustomId = $value->enquiryCustomId;
            $myObj->adult = $value->adult;
            $myObj->extraBed = $value->extraBed;
            $myObj->childWithout = $value->childWithout;

            $roomsArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $roomsArray
        ), 200);
    }

    //guest details custom tour
    public function customGuestDetails(Request $request)
    {
        $rules = [
            'customGuestDetails.*.familyHeadName' => 'required|string',
            'customGuestDetails.*.gender' => 'required',
            'customGuestDetails.*.address' => 'required',
            'customGuestDetails.*.mailId' => 'required|email',
            // 'customGuestDetails.*.relationId' => 'required',
            'customGuestDetails.*.dob' => 'required',
            'customGuestDetails.*.marriageDate' => 'required',
            'customGuestDetails.*.adharCard' => 'required',
            'customGuestDetails.*.passport' => 'required',
            'customGuestDetails.*.adharNo' => 'required',
            'customGuestDetails.*.panNo' => 'required',
            'customGuestDetails.*.pan' => 'required',


            'customTourDiscountDetails.*.tourPrice' => 'required|numeric|min:0',
            'customTourDiscountDetails.*.discountPrice' => 'required|min:0',
            'customTourDiscountDetails.*.gst' => 'required|min:0',
            'customTourDiscountDetails.*.tcs' => 'required|min:0',
            'customTourDiscountDetails.*.grandTotal' => 'required|min:0',
            'customTourDiscountDetails.*.billingName' => 'required|string',
            'customTourDiscountDetails.*.address' => 'required',
            'customTourDiscountDetails.*.phoneNo' => 'required|digits:10',
            'customTourDiscountDetails.*.gstin' => 'required',
            'customTourDiscountDetails.*.panNo' => 'required',


            'customTourPaymentDetails.*.balance' => 'required|min:0',
            'customTourPaymentDetails.*.paymentModeId' => 'required',
            'customTourPaymentDetails.*.bankName' => 'required',
            'customTourPaymentDetails.*.paymentDate' => 'required',
            'customTourPaymentDetails.*.transactionId' => 'required',
            'customTourPaymentDetails.*.transactionProof' => 'required'
        ];

        $validation = Validator::make($request->all(), $rules);

        if ($validation->fails()) {
            return response(["message" => $validation->errors()->all()], 400);
        }

        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        try {


            $enquiryDetails =  DB::table('enquiryCustomTours')
                ->where('enquiryCustomId', $request->enquiryCustomId)
                ->first();
            // dd($enquiryDetails);
            if (!$enquiryDetails) {
                return response()->json(['message' => 'Enquiry details not found'], 500);
            }

            //start transaction
            DB::beginTransaction();
            //insert data in table
            $guestId = '';
            $guest = [];
            foreach ($request->customGuestDetails as $key => $value) {
                $namePrefix = substr($value['familyHeadName'], 0, 2);
                if ($key !== 0) {
                    $guestId = $namePrefix . rand(100000, 999999);
                }
                $guest[] = [
                    'enquiryCustomId' => $request->enquiryCustomId,
                    'enquiryDetailCustomId' => $request->enquiryDetailCustomId,
                    'familyHeadName' => $value['familyHeadName'],
                    'gender' => $value['gender'],
                    'contact' => $value['contact'],
                    'roomShareType' => $value['roomShareType'],
                    'address' => $value['address'],
                    'mailId' => $value['mailId'],
                    'relationId' => 1,
                    'dob' => $value['dob'],
                    'marriageDate' => $value['marriageDate'],
                    'adharCard' => $value['adharCard'],
                    'passport' => $value['passport'],
                    'pan' => $value['panNo'],
                    'adharNo' => $value['adharNo'],
                    'panNo' => $value['panNo'],
                    'guestId' => $guestId,
                    'createdBy' => $tokenData->userId
                ];
            }
            if (empty($guest)) {
                return ['message' => 'Guest array is empty', 'status' => true];
            }
            // dd($guest);
            $tourPrice = collect($guest)->sum('roomShareType');
            // dd($tourPrice);
            $guestDetails = DB::table('customTourGuestDetails')->insert($guest);

            if (!$guestDetails) {
                return ['message' => 'Custom Guest details not added', 'status' => false];
            }

            //discount details
            $discountPrice = $tourPrice - $request->additionalDis;
            $gst = $discountPrice * 5 / 100;
            $tcs = ($discountPrice + $gst) * 5 / 100;
            $grandTotal = $discountPrice + $gst + $tcs;

            $discountGuestDetail = DB::table('customTourDiscountDetails')->insertGetId([
                'enquiryCustomId' => $request->enquiryCustomId,
                'enquiryDetailCustomId' => $request->enquiryDetailCustomId,
                'tourPrice' => $tourPrice,
                'additionalDis' => $request->additionalDis,
                'discountPrice' =>  $discountPrice,
                'gst' => $gst,
                'tcs' => $tcs,
                'grandTotal' =>  $grandTotal,
                'billingName' => $request->billingName,
                'address' => $request->address,
                'phoneNo' => $request->phoneNo,
                'gstIn' => $request->gstIn,
                'panNo' => $request->panNo,
                'createdBy' => $tokenData->userId
            ]);

            $payDetailsCustom = DB::table('customTourPaymentDetails')->insert([
                'enquiryCustomId' => $request->enquiryCustomId,
                'enquiryDetailCustomId' => $request->enquiryDetailCustomId,
                'customDisId' =>  $discountGuestDetail,
                'advancePayment' => $request->advancePayment,
                'balance' => $grandTotal - $request->advancePayment,
                'paymentModeId' => $request->paymentModeId,
                'bankName' => $request->bankName,
                'chequeNo' => $request->chequeNo,
                'payDate' => $request->payDate,
                'transactionId' => $request->transactionId,
                'transactionProof' => $request->transactionProof,
                'createdBy' => $tokenData->userId

            ]);

            DB::commit();
            $enquiryStatus = DB::table('enquiryCustomTours')->where('enquiryCustomId', $request->enquiryCustomId)->update([
                'enquiryProcess' => 2
            ]);
            return response()->json(['message' => "Custom enquiry confirmed successfully"], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function ct(Request $request)
    {
        $ct = DB::table('customTourEnquiryDetails')
            ->join('customTourPaymentDetails', 'customTourEnquiryDetails.enquiryDetailCustomId', '=', 'customTourPaymentDetails.enquiryDetailCustomId')
            ->join('customTourDiscountDetails', 'customTourEnquiryDetails.enquiryDetailCustomId', '=', 'customTourDiscountDetails.enquiryDetailCustomId')
            ->join('enquiryCustomTours', 'customTourEnquiryDetails.enquiryCustomId', '=', 'enquiryCustomTours.enquiryCustomId')
            ->where('enquiryCustomTours.enquiryProcess',  '=', 2)
            ->where('customTourPaymentDetails.status', 1)
            ->select('enquiryCustomTours.*', 'customTourDiscountDetails.*', 'customTourPaymentDetails.*', 'customTourEnquiryDetails.enquiryDetailCustomId');
        $confirmCustomTour = $ct->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($confirmCustomTour->isEmpty()) {
            $confirmCustomArray = [];
        }
        $myObj = new \stdClass();
        foreach ($confirmCustomTour as $key => $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId;
            $myObj->groupName = $value->groupName;
            $myObj->enquiryDetailCustomId = $value->enquiryDetailCustomId;
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
        ), 200);;
    }

    //confirm custom tour listing
    public function confirmCustomList(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1, 2, 4]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        //listing
        $confirmCustom = DB::table('customTourEnquiryDetails')
            ->join('customTourPaymentDetails', 'customTourEnquiryDetails.enquiryDetailCustomId', '=', 'customTourPaymentDetails.enquiryDetailCustomId')
            ->join('customTourDiscountDetails', 'customTourEnquiryDetails.enquiryDetailCustomId', '=', 'customTourDiscountDetails.enquiryDetailCustomId')
            ->join('enquiryCustomTours', 'customTourEnquiryDetails.enquiryCustomId', '=', 'enquiryCustomTours.enquiryCustomId')
            ->where('enquiryCustomTours.enquiryProcess',  '=', 2)
            ->where('enquiryCustomTours.createdBy',  '=', $tokenData->userId)
            ->where('customTourPaymentDetails.status', 1)
            ->select(
                'enquiryCustomTours.*',
                'customTourDiscountDetails.*',
                'customTourPaymentDetails.*',
                'customTourEnquiryDetails.enquiryDetailCustomId'
            );

        $confirmCustomTour = $confirmCustom->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($confirmCustomTour->isEmpty()) {
            $confirmCustomArray = [];
        }
        $myObj = new \stdClass();
        foreach ($confirmCustomTour as $key => $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId;
            $myObj->groupName = $value->groupName;
            $myObj->enquiryDetailCustomId = $value->enquiryDetailCustomId;
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

    //view billing custom tour
    public function viewBillCT(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'enquiryCustomId' => 'required|numeric'
        ]);
        if ($validateData->fails()) {
            return response()->json(["message" => $validateData->erros()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $paymentDetails = DB::table('customTourDiscountDetails')
            ->join('customTourPaymentDetails', 'customTourDiscountDetails.enquiryDetailCustomId', '=', 'customTourPaymentDetails.enquiryDetailCustomId')
            ->join('customTourEnquiryDetails', 'customTourDiscountDetails.enquiryDetailCustomId', '=', 'customTourEnquiryDetails.enquiryDetailCustomId')
            ->where('customTourPaymentDetails.status', 1)
            ->where('customTourDiscountDetails.enquiryDetailCustomId', $request->enquiryCustomId)

            ->select(
                'customTourDiscountDetails.*',
                'customTourPaymentDetails.advancePayment',
                'customTourPaymentDetails.balance',
                'customTourPaymentDetails.customPayDetailId',
                'customTourEnquiryDetails.enquiryDetailCustomId',
                'customTourEnquiryDetails.familyHeadName'
            )
            ->get();
        // dd($paymentDetails);
        if ($paymentDetails->isEmpty()) {
            return response()->json(['message' => 'No payment details found for the given ID'], 404);
        }
        $advancePayments = [];
        $balance = 0;
        foreach ($paymentDetails as $paymentDetail) {
            $advancePayments[] = [
                'customPayDetailId' => $paymentDetail->customPayDetailId,
                'advancePayment' => $paymentDetail->advancePayment,
            ];

            $balance = $paymentDetail->balance;
        }
        $paymentDetails = [
            'enquiryCustomId' => $paymentDetails[0]->enquiryCustomId,
            'enquiryDetailCustomId' => $paymentDetails[0]->enquiryDetailCustomId,
            'billingName' => $paymentDetails[0]->billingName,
            'address' => $paymentDetails[0]->address,
            'phoneNumber' => $paymentDetails[0]->phoneNo,
            'gstIn' => $paymentDetails[0]->gstIn,
            'panNumber' => $paymentDetails[0]->panNo,
            'grandTotal' => $paymentDetails[0]->grandTotal,
            'advancePayments' => $advancePayments,
            'balance' => $balance
        ];
        // dd($paymentDetails);
        return response()->json(array(
            'data' => $paymentDetails,
        ), 200);
    }

    //receive bill custom group tour
    public function receiveBillCT(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'enquiryCustomId' => 'required|numeric',
            'enquiryDetailCustomId' => 'required|numeric'
        ]);
        if ($validateData->fails()) {
            return response()->json(["message" => $validateData->errors()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $details = DB::table('customTourDiscountDetails')
            ->where('enquiryCustomId', $request->enquiryCustomId)
            ->where('enquiryDetailCustomId', $request->enquiryDetailCustomId)
            ->first();
        // dd($details);

        $existingBalance = DB::table('customTourPaymentDetails')
            ->where('enquiryCustomId', $request->enquiryCustomId)
            ->where('enquiryDetailCustomId', $request->enquiryDetailCustomId)
            ->orderBy('created_at', 'desc')
            ->value('balance');
        // dd( $existingBalance);

        // Calculate the new balance
        $newBalance = $existingBalance - $request->advancePayment;
        $newPayment = DB::table('customTourPaymentDetails')->insert([
            'enquiryCustomId' => $request->enquiryCustomId,
            'enquiryDetailCustomId' => $request->enquiryDetailCustomId,
            'customDisId' =>  $details->customDisId,
            'advancePayment' => $request->advancePayment,
            'balance' =>   $newBalance,
            'paymentModeId' => $request->paymentModeId,
            'onlineTypeId' => $request->onlineTypeId,
            'bankName' => $request->bankName,
            'chequeNo' => $request->chequeNo,
            'payDate' => $request->payDate,
            'transactionId' => $request->transactionId,
            'transactionProof' => $request->transactionProof
        ]);
        if ($newPayment) {
            return response(["message" => "New Payment added successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }

    //view custom -tour
    public function viewCustomTour(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        //get group tour listing
        $customTours = DB::table('enquiryCustomTours')
            ->join('customTourDiscountDetails', 'enquiryCustomTours.enquiryCustomId', '=', 'customTourDiscountDetails.enquiryCustomId')
            ->join('customTourPaymentDetails', 'enquiryCustomTours.enquiryCustomId', '=', 'customTourPaymentDetails.enquiryCustomId')
            ->where('enquiryCustomTours.enquiryProcess',  '=', 2)
            ->where('customTourPaymentDetails.status', 1)
            ->select('enquiryCustomTours.*', 'customTourDiscountDetails.*', 'customTourPaymentDetails.*');



        // searching
        $customTours->where(function ($q) use ($request) {

            if (!empty($request->groupName)) {
                $q->orWhere('groupName', 'like', '%' . $request->groupName . '%');
            }
            if (!empty($request->startDate)) {
                $q->orWhere('startDate', 'like', '%' . $request->startDate . '%');
            }
            if (!empty($request->endDate)) {
                $q->orWhere('endDate', 'like', '%' . $request->endDate . '%');
            }
            if (!empty($request->departureType)) {
                $q->orWhere('departureId', 'like', '%' . $request->departureId . '%');
            }
            if (!empty($request->departureType)) {
                $q->orWhere('departureCity', 'like', '%' . $request->departureCity . '%');
            }
        });
        $confirmCustomTour = $customTours->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($confirmCustomTour->isEmpty()) {
            $confirmCustomArray = [];
        }
        $myObj = new \stdClass();
        foreach ($confirmCustomTour as $key => $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId;
            $myObj->groupName = $value->groupName;
            $myObj->startDate = $value->startDate;
            $myObj->endDate = $value->endDate;
            $myObj->duration = $value->days + $value->nights;
            $myObj->additionalDis = $value->additionalDis;
            $myObj->gst = $value->gst;

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

    //dropdown country state
    public function countryState(Request $request)
    {
        $destinationId = $request->destinationId;
        if ($destinationId == 1) {
            $states = DB::table('dropdownCities')->paginate();
            if ($states->isEmpty()) {
                $states_array = [];
            }
            $myObj = new \stdClass();
            foreach ($states as $key => $value) {
                $myObj->cityId   = $value->cityId;
                $myObj->cityName = $value->cityName;

                $states_array[] = $myObj;
                $myObj = new \stdClass();
            }
            return response()->json(array(
                'data' => $states_array,
            ), 200);
        } elseif ($destinationId == 2) {
            $countryList = DB::table('dropdowncountry')->paginate();
            if ($countryList->isEmpty()) {
                $countryList_array = [];
            }
            $myObj = new \stdClass();
            foreach ($countryList as  $value) {
                $myObj->countryId  = $value->countryId;
                $myObj->countryName = $value->countryName;

                $countryList_array[] = $myObj;
                $myObj = new \stdClass();
            }
            return response()->json(array(
                'data' => $countryList_array,
            ), 200);
        }
    }
}
