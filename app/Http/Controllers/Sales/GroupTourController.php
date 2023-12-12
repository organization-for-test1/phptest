<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Http\Controllers\CommonController;
use stdClass;

use function Laravel\Prompts\select;

class GroupTourController extends Controller
{
    //group-tour listing
    public function groupTourDropdown(Request $request)
    {
        $groupTours = DB::table('grouptours')->get();
        // dd($groupTours);
        if ($groupTours->isEmpty()) {
            $groupTours_array = [];
        }
        $myObj = new \stdClass();
        foreach ($groupTours as $key => $value) {
            $myObj->groupTourId = $value->groupTourId;
            $myObj->tourName = $value->tourName;

            $groupTours_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $groupTours_array,
        ), 200);
    }

    //enquiry group-tour
    public function enquiryGroupTour(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'guestName' => 'required|max:20',
            'contact' => 'required|digits:10',
            'mail' => 'required|email',
            'adults' => 'required|numeric',
            'nextFollowUp' => 'required',
            'enquiryReferId' => 'required|numeric',
            'priorityId' => 'required|numeric',
            'groupTourId' => 'required'
        ], [
            'contact.digits' => 'The  contact number must be 10 digits numeric',
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        try {
            $namePrefix = substr($request->guestName, 0, 2);
            $guestId = $namePrefix . rand(100000, 999999);
            // dd($guestId);
            $enquiryGroup = DB::table('enquirygrouptours')->insert([
                'groupTourId' => $request->groupTourId,
                'enquiryReferId' => $request->enquiryReferId,
                'priorityId' => $request->priorityId,
                'guestName' => $request->guestName,
                'contact' => $request->contact,
                'mail' => $request->mail,
                'adults' => $request->adults,
                'child' => $request->child,
                'guestRefId' => $request->guestRefId,
                'nextFollowUp' => $request->nextFollowUp,
                'guestId' => $guestId,
                'createdBy' => $tokenData->userId,
            ]);
            // dd($enquiryGroup);
            if ($enquiryGroup) {
                return response(["message" => "Enquiry for Group Tour added successfully"], 200);
            } else {
                return response(["message" => "Something Went Wrong"], 500);
            }
        } catch (\Exception $e) {
            return response()->json(["message" => $e->getMessage()], 400);
        }
    }

    //listing group tour - (enquiry follow-up)
    public function listGroupTour(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]); //SALES&OPERATIONS
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $today = now()->toDateString();
        // dd($today);

        $groupTourInquiry = DB::table('enquirygrouptours')
            ->join('grouptours', 'enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
            ->where('enquirygrouptours.enquiryProcess', 1)
            ->whereDate('enquirygrouptours.nextFollowUp', $today)
            ->where('enquirygrouptours.createdBy', $tokenData->userId)
            ->select('enquirygrouptours.*', 'grouptours.tourName', 'grouptours.startDate', 'grouptours.endDate')
            ->orderBy('enquirygrouptours.enquiryGroupId', 'desc');
        // ->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($request->startDate != '' && $request->endDate != '') {
            $start_datetime = Carbon::parse($request->startDate)->startOfDay();
            $end_datetime = Carbon::parse($request->endDate)->endOfDay();

            $groupTourInquiry->where(function ($query) use ($start_datetime, $end_datetime) {
                $query->where('startDate', '>=', $start_datetime)
                    ->where('endDate', '<=', $end_datetime);
            });
        } elseif ($request->startDate != '') {
            $start_datetime = Carbon::parse($request->startDate)->startOfDay();
            $groupTourInquiry->where('startDate', '>=', $start_datetime);
        } elseif ($request->endDate != '') {
            $end_datetime = Carbon::parse($request->endDate)->endOfDay();
            $groupTourInquiry->where('endDate', '<=', $end_datetime);
        } elseif (!empty($request->search)) {
            $search = $request->search;
            $groupTourInquiry->where('guestName', 'like', '%' . $search . '%');
        } elseif (!empty($request->search)) {
            $search = $request->search;
            $groupTourInquiry->where('tourName', 'like', '%' . $search . '%');
        }

        $groupTourInquiries = $groupTourInquiry->paginate($request->perPage == null ? 10 : $request->perPage);

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

    // upacomingListGroupTour
    public function upcomingListGroupTour(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]); //SALES&OPERATIONS
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $today = now()->toDateString();

        $groupTour = DB::table('enquirygrouptours')
            ->join('grouptours', 'enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
            ->where('enquirygrouptours.enquiryProcess', 1)
            ->where('enquirygrouptours.nextFollowUp', '>', $today)
            ->where('enquirygrouptours.createdBy', $tokenData->userId)
            ->select('enquirygrouptours.*', 'grouptours.tourName', 'grouptours.startDate', 'grouptours.endDate')
            ->orderBy('enquirygrouptours.enquiryGroupId', 'desc')
            ->paginate($request->perPage == null ? 10 : $request->perPage);
        // dd($groupTour);

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

    //next-follow-up update
    public function updateGroupFollowUp(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'enquiryGroupId' => 'required|numeric',
            'nextFollowUp' => 'required',
            'remark' => 'required'
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        try {
            $updateFollowUp = DB::table('enquirygrouptours')
                ->where('enquiryGroupId', $request->enquiryGroupId)
                ->where('enquirygrouptours.createdBy', $tokenData->userId)
                ->update([
                    'nextFollowUp' => $request->nextFollowUp,
                    'remark' => $request->remark
                ]);
            if ($updateFollowUp) {
                return response(["message" => "Follow Up updated successfully"], 200);
            } else {
                return response(["message" => "Something Went Wrong"], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    //view group tour (view tours)
    public function viewGroupTour(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 3]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        //get group tour listing
        $groupTours = DB::table('grouptours')
            ->join('tourtype', 'grouptours.tourTypeId', 'tourtype.tourTypeId')
            ->select('grouptours.*', 'tourtype.tourTypeName');


        //searching
        $groupTours->where(function ($q) use ($request) {
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
            if (!empty($request->departureType)) {
                $q->orWhere('departureId', 'like', '%' . $request->endDate . '%');
            }
        });
        $groupToursDetails = $groupTours->paginate($request->perPage == null ?  10 : $request->perPage);
        if ($groupToursDetails->isEmpty()) {
            $groupTours_array = [];
        }

        $myObj = new \stdClass();
        foreach ($groupToursDetails as $key => $value) {
            $myObj->groupTourId  = $value->groupTourId;
            $myObj->tourName = $value->tourName;
            $myObj->tourCode = $value->tourCode;
            $myObj->tourTypeName = $value->tourTypeName;
            $myObj->startDate = $value->startDate;
            $myObj->endDate = $value->endDate;
            $myObj->duration = $value->days . 'D-' . $value->night . 'N';
            $myObj->totalSeats = $value->totalSeats;

            $enquiryGroupTour = DB::table('enquirygrouptours')
                ->where('enquiryProcess', 2)
                ->where('groupTourId', $value->groupTourId)
                ->selectRaw('SUM(adults + child) as totalGuests')
                ->first();

            $availableSeats = $myObj->totalSeats - $enquiryGroupTour->totalGuests;
            $myObj->seatsAval = $availableSeats;
            $seatsbook = $myObj->totalSeats - $availableSeats;
            $myObj->seatsBook = $seatsbook;

            $groupTours_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $groupTours_array,
            'total' => $groupToursDetails->total(),
            'currentPage' => $groupToursDetails->currentPage(),
            'perPage' => $groupToursDetails->perPage(),
            'nextPageUrl' => $groupToursDetails->nextPageUrl(),
            'previousPageUrl' => $groupToursDetails->previousPageUrl(),
            'lastPage' => $groupToursDetails->lastPage()
        ), 200);
    }

    //enquiry deails
    public function enqGroupDetails(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'enquiryGroupId' => 'required',
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }

        $enqDetails =  DB::table('enquirygrouptours')->where('enquiryGroupId', $request->enquiryGroupId)
            ->join('grouptours', 'enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
            ->select('enquirygrouptours.*', 'grouptours.destinationId', 'grouptours.tourName', 'grouptours.tourCode')
            ->first();
        // dd($enqDetails);
        if ($enqDetails) {
            return response()->json([
                'guestName' => $enqDetails->guestName,
                'destinationId' => $enqDetails->destinationId,
                'email' => $enqDetails->mail,
                'contact' => $enqDetails->contact,
                'tourName' => $enqDetails->tourName,
                'tourCode' => $enqDetails->tourCode,
                'paxNo' => $enqDetails->adults + $enqDetails->child,
                'guestRef' => $enqDetails->guestRefId,
                'guestId' => $enqDetails->guestId
            ], 200);
        }
    }

    //dropdown travel mode
    public function dropdowbTravelMode(Request $request)
    {
        $departureTypeId = $request->departureTypeId;
        if ($departureTypeId == 1) {
            $trvelMode = DB::table('dropdowntravelmode')->paginate();
            if ($trvelMode->isEmpty()) {
                $trvelModeArray = [];
            }
            $myObj = new stdClass();
            foreach ($trvelMode as  $value) {
                $myObj->travelId  = $value->travelId;
                $myObj->traveModeName = $value->traveModeName;

                $trvelModeArray[] = $myObj;
                $myObj = new stdClass();
            }
            return response()->json(['data' =>  $trvelModeArray], 200);
        } elseif ($departureTypeId == 2) {
            $trvelMode = DB::table('dropdowntravelmode')->where('travelId', 2)->paginate();
            if ($trvelMode->isEmpty()) {
                $trvelModeArray = [];
            }
            $myObj = new stdClass();
            foreach ($trvelMode as $value) {
                $myObj->travelId = $value->travelId;
                $myObj->traveModeName = $value->traveModeName;

                $trvelModeArray[] = $myObj;
                $myObj = new stdClass();
            }
            return response()->json(['data' => $trvelModeArray], 200);
        } elseif ($departureTypeId == 3) {
            $trvelMode = DB::table('dropdowntravelmode')->where('travelId', 1)->paginate();
            if ($trvelMode->isEmpty()) {
                $trvelModeArray = [];
            }
            $myObj = new stdClass();
            foreach ($trvelMode as $value) {
                $myObj->travelId = $value->travelId;
                $myObj->traveModeName = $value->traveModeName;

                $trvelModeArray[] = $myObj;
                $myObj = new stdClass();
            }
            return response()->json(['data' => $trvelModeArray], 200);
        }
    }

    //enquiry group-tour details
    public function enquiryGroupDetails(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            // 'guestType' => 'required',
            'departureTypeId' => 'required',
            'arrivalTime' => 'required|date_format:H:i',
            'travelId' => 'required',
            // 'paxNo' => 'required|max:6'

        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }
        $rules = [
            'groupGuestDetails.*.familyHeadName' => 'required|string|min:3|max:30',
            'groupGuestDetails.*.gender' => 'required',
            'groupGuestDetails.*.address' => 'required',
            'groupGuestDetails.*.contact' => 'required|digits:10',
            'groupGuestDetails.*.mailId' => 'required|email',
            'groupGuestDetails.*.roomShareId' => 'required',
            'groupGuestDetails.*.dob' => 'required',
            'groupGuestDetails.*.adharCard' => 'required',
            'groupGuestDetails.*.passport' => 'required',
            'groupGuestDetails.*.adharNo' => 'required|digits:12',
            'groupGuestDetails.*.panNo' => 'required|regex:/^[a-zA-Z0-9]{10}$/',
            'groupGuestDetails.*.pan' => 'required',

            'grouptourdiscountdetails.*.tourPrice' => 'required|numeric|min:0',
            'grouptourdiscountdetails.*.discountPrice' => 'required|min:0',
            'grouptourdiscountdetails.*.gst' => 'required|min:0',
            'grouptourdiscountdetails.*.tcs' => 'required|min:0',
            'grouptourdiscountdetails.*.grandTotal' => 'required|min:0',
            'groupdiscountdetails.*.billingName' => 'required|string|max:20',
            'groupdiscountdetails.*.address' => 'required',
            'groupdiscountdetails.*.phoneNo' => 'required|digits:10',
            'groupdiscountdetails.*.gstin' => 'required',
            'groupdiscountdetails.*.panNo' => 'required|regex:/^[a-zA-Z0-9]{10}$/',

            'grouptourpaymentdetails.*.advancePayment' => 'required|min:0',
            'grouptourpaymentdetails.*.balance' => 'required|min:0',
            'grouptourpaymentdetails.*.paymentModeId' => 'required',
            'grouptourpaymentdetails.*.bankName' => 'required',
            'grouptourpaymentdetails.*.paymentDate' => 'required|date_format:d-m-Y',
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
            $enqDetails =  DB::table('enquirygrouptours')->where('enquiryGroupId', $request->enquiryGroupId)->first();
            if (!$enqDetails) {
                return response()->json(['message' => 'Enq detail not found'], 500);
            }
            //start transaction
            DB::beginTransaction();
            $enquiryGroupDetail = DB::table('grouptourenquirydetails')->insertGetId([
                'enquiryGroupId' => $request->enquiryGroupId,
                'groupTourId' => $enqDetails->groupTourId,
                'travelMode' => $request->travelId,
                'departureTypeId' => $request->departureTypeId,
                'paxNo' => $request->paxNo,
                'arrivalTime' => $request->arrivalTime,
                'guestRefId' => $request->guestRefId
            ]);

            if (!$enquiryGroupDetail) {
                return response()->json(['message' => 'Group not added'], 404);
            }

            //group tour guest details
            $guestId = '';
            $guest = [];

            foreach ($request->groupGuestDetails as $key => $value) {
                $namePrefix = substr($value['familyHeadName'], 0, 2);

                if ($key !== 0) {
                    $guestId = $namePrefix . rand(100000, 999999);
                }


                $guest[] = [
                    'enquiryGroupId' => $request->enquiryGroupId,
                    'groupTourId' => $enqDetails->groupTourId,
                    'familyHeadName' => $value['familyHeadName'],
                    'gender' => $value['gender'],
                    'contact' => $value['contact'],
                    'address' => $value['address'],
                    'mailId' => $value['mailId'],
                    'guestId' => $guestId,
                    'roomShareId' => $value['roomShareId'],
                    'dob' => $value['dob'],
                    'marriageDate' => $value['marriageDate'] ?? null,
                    'adharCard' => $value['adharCard'],
                    'adharNo' => $value['adharNo'],
                    'passport' => $value['passport'],
                    'pan' => $value['pan'],
                    'panNo' => $value['panNo'],
                    'createdBy' => $tokenData->userId,
                ];
            }

            if (empty($guest)) {
                return response()->json(['message' => 'Guest array is empty'], 400);
            }

            $guestDetails = DB::table('grouptourguestdetails')->insert($guest);
            if (!$guestDetails) {
                return response()->json(['message' => 'Group Guest details not added'], 400);
            }




            $discount = DB::table('grouptourdiscountdetails')->insertGetId([
                'enquiryGroupId' => $request->enquiryGroupId,
                'groupTourId' => $enqDetails->groupTourId,
                'tourPrice' => $request->tourPrice,
                'additionalDis' => $request->additionalDis,
                'discountPrice' =>  $request->discountPrice,
                'gst' =>  $request->gst,
                'tcs' => $request->tcs,
                'grandTotal' =>  $request->grandTotal,
                'billingName' => $request->billingName,
                'address' => $request->address,
                'phoneNo' => $request->phoneNo,
                'gstin' => $request->gstin,
                'panNo' => $request->panNo,
                'createdBy' => $tokenData->userId,

            ]);

            // dd($discount);
            $groupDisId = $discount;

            $paymentDetails = DB::table('grouptourpaymentdetails')->insert([
                'enquiryGroupId' => $request->enquiryGroupId,
                'groupTourId' => $enqDetails->groupTourId,
                'groupDisId' => $groupDisId,
                'advancePayment' => $request->advancePayment,
                'balance' =>  $request->balance,
                'paymentModeId' => $request->paymentModeId,
                'bankName' => $request->bankName,
                'chequeNo' => $request->chequeNo,
                'paymentDate' => $request->paymentDate,
                'transactionId' => $request->transactionId,
                'transactionProof' => $request->transactionProof,
                'createdBy' => $tokenData->userId
            ]);
            DB::commit();

            $enquiryStatus = DB::table('enquirygrouptours')->where('enquiryGroupId', $request->enquiryGroupId)->update([
                'enquiryProcess' => "2"
            ]);
            return response()->json(['message' => 'Group tour enquiry details added successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    //discountdetail
    public function discountDetails(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $discount = DB::table('grouptourdiscountdetails')->insertGetId([
            'enquiryGroupId' => $request->enquiryGroupId,
            'groupTourId' => $request->groupTourId,
            'tourPrice' => $request->tourPrice,
            'additionalDis' => $request->additionalDis,
            'discountPrice' =>  $request->discountPrice,
            'gst' =>  $request->gst,
            'tcs' => $request->tcs,
            'grandTotal' =>  $request->grandTotal,
            'billingName' => $request->billingName,
            'address' => $request->address,
            'phoneNo' => $request->phoneNo,
            'gstin' => $request->gstin,
            'panNo' => $request->panNo,
            'createdBy' => $tokenData->userId,

        ]);
        if ($discount) {
            return response()->json(['message' => 'Discount details added successfully'], 200);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    //paymentdetails
    public function paymentDetails(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $paymentDetails = DB::table('grouptourpaymentdetails')->insert([
            'enquiryGroupId' => $request->enquiryGroupId,
            'groupTourId' => $request->groupTourId,
            'groupDisId' => $request->groupDisId,
            'advancePayment' => $request->advancePayment,
            'balance' =>  $request->balance,
            'paymentModeId' => $request->paymentModeId,
            'bankName' => $request->bankName,
            'chequeNo' => $request->chequeNo,
            'paymentDate' => $request->paymentDate,
            'transactionId' => $request->transactionId,
            'transactionProof' => $request->transactionProof,
            'createdBy' => $tokenData->userId
        ]);


        $enquiryStatus = DB::table('enquirygrouptours')->where('enquiryGroupId', $request->enquiryGroupId)->update([
            'enquiryProcess' => "2"
        ]);
        return response()->json(['message' => 'Group tour enquiry details added successfully'], 200);
    }


    //confirm group tour list
    public function confirmGroupTourList(Request $request)
    {
        //cheks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 4]); //for sales & accounts
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $confirmGroupTour = DB::table('enquirygrouptours')
            ->join('grouptours', 'enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
            ->join('grouptourdiscountdetails', 'enquirygrouptours.enquiryGroupId', '=', 'grouptourdiscountdetails.enquiryGroupId')
            ->join('grouptourpaymentdetails', 'enquirygrouptours.enquiryGroupId', '=', 'grouptourpaymentdetails.enquiryGroupId')
            ->where('enquirygrouptours.enquiryProcess', '=', 2)
            // ->where('grouptourpaymentdetails.status', 1)
            ->where('enquirygrouptours.createdBy', $tokenData->userId)
            ->select('grouptours.tourName', 'grouptours.startDate', 'grouptours.endDate', 'enquirygrouptours.enquiryGroupId', 'enquirygrouptours.guestName', 'enquirygrouptours.contact', 'grouptourdiscountdetails.*', 'grouptourpaymentdetails.*')
            ->orderBy('enquirygrouptours.enquiryGroupId', 'desc');

        // dd($confirmGroupTour);
        if (!empty($request->search) || $request->search != "" || $request->search != null) {
            $search = $request->search;
            $confirmGroupTour->where(function ($q) use ($search) {
                $q->where('tourName', 'like', '%' . $search . '%')
                    ->orWhere('guestName', 'like', '%', $search . '%');
            });
        }
        $confirmGroupTourDetails = $confirmGroupTour->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($confirmGroupTourDetails->isEmpty()) {
            $confirmGroupTourArray = [];
        }
        $myObj = new \stdClass();
        foreach ($confirmGroupTourDetails as  $value) {
            $myObj->enquiryGroupId = $value->enquiryGroupId;
            $myObj->tourName = $value->tourName;
            $myObj->guestName = $value->guestName;
            $myObj->startDate = $value->startDate;
            $myObj->endDate = $value->endDate;
            $myObj->contact = $value->contact;
            $myObj->tourPrice = $value->tourPrice;
            $myObj->discount = $value->additionalDis;
            $myObj->discounted = $value->discountPrice;
            $myObj->gst = $value->gst;
            $myObj->tcs = $value->tcs;
            $myObj->grand = $value->grandTotal;
            $myObj->advancePayment = $value->advancePayment;
            $myObj->balance = $value->balance;


            $confirmGroupTourArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $confirmGroupTourArray,
            'total' => $confirmGroupTourDetails->total(),
            'currentPage' => $confirmGroupTourDetails->currentPage(),
            'perPage' => $confirmGroupTourDetails->perPage(),
            'nextPageUrl' => $confirmGroupTourDetails->nextPageUrl(),
            'previousPageUrl' => $confirmGroupTourDetails->previousPageUrl(),
            'lastPage' => $confirmGroupTourDetails->lastPage()
        ), 200);
    }

    //view  billing confirm group tour
    public function viewBillGroupTour(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'enquiryGroupId' => "required|numeric"
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }
        //cheks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 4]); //sales,account
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $paymentDetails = DB::table('grouptourpaymentdetails')
            ->join('enquirygrouptours', 'grouptourpaymentdetails.enquiryGroupId', '=', 'enquirygrouptours.enquiryGroupId')
            ->where('grouptourpaymentdetails.enquiryGroupId', $request->enquiryGroupId)
            ->where('grouptourpaymentdetails.status', 1)
            ->select('enquirygrouptours.*', 'grouptourpaymentdetails.advancePayment', 'grouptourpaymentdetails.balance', 'grouptourpaymentdetails.groupPaymentDetailId')
            ->get();

        if ($paymentDetails->isEmpty()) {
            return response()->json(['error' => 'No confirm payment details found for the given criteria.'], 404);
        }
        $discountDetails = DB::table('grouptourdiscountdetails')->where('enquiryGroupId', $request->enquiryGroupId)->first();
        // dd($discountDetails);

        $advancePayments = [];
        $balance = 0;
        foreach ($paymentDetails as $paymentDetail) {
            $advancePayments[] = [
                'groupPaymentDetailId' => $paymentDetail->groupPaymentDetailId,
                'advancePayment' => $paymentDetail->advancePayment,
            ];
            $balance = $paymentDetail->balance;
        }

        $paymentDetails = [
            'enquiryGroupId' => $paymentDetails[0]->enquiryGroupId,
            // 'groupDisId' => $paymentDetails[0]->groupDisId,
            'billingName' => $discountDetails->billingName,
            'address' => $discountDetails->address,
            'phoneNumber' => $discountDetails->phoneNo,
            'gstIn' => $discountDetails->gstin,
            'panNumber' => $discountDetails->panNo,
            'grandTotal' => $discountDetails->grandTotal,
            'advancePayments' => $advancePayments,
            'balance' => $balance
        ];
        // dd($paymentDetails);
        return response()->json(array(
            'data' => $paymentDetails,
        ), 200);
    }

    //receive bill group tour
    public function receiveBillGroupTour(Request $request)
    {
        // dd("here");
        $validateData = Validator::make($request->all(), [
            'enquiryGroupId' => 'required|numeric'
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }
        //cheks token
        $tokenData = CommonController::checkToken($request->header('token'), [2, 4]); //sales,accounts
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $existingBalance = DB::table('grouptourpaymentdetails')
            ->where('enquiryGroupId', $request->enquiryGroupId)
            ->orderBy('created_at', 'desc')
            ->value('balance');
        // dd( $existingBalance);
        $enquiryGroupId = DB::table('enquirygrouptours')->where('enquiryGroupId', $request->enquiryGroupId)->first();
        // dd($enquiryGroupId);
        $createdBy = $enquiryGroupId->createdBy;
        // dd($createdBy);


        //detail of discount table
        $details = DB::table('grouptourdiscountdetails')->where('enquiryGroupId', $request->enquiryGroupId)->first();
        // dd($details);
        // Calculate the new balance
        $newBalance = $existingBalance - $request->advancePayment;

        $newPayment = DB::table('grouptourpaymentdetails')->insert([
            'enquiryGroupId' => $request->enquiryGroupId,
            'groupDisId' => $details->groupDisId,
            'groupTourId' => $details->groupTourId,
            'advancePayment' => $request->advancePayment,
            'balance' =>   $newBalance,
            'paymentModeId' => $request->paymentModeId,
            'onlineTypeId' => $request->onlineTypeId,
            'bankName' => $request->bankName,
            'chequeNo' => $request->chequeNo,
            'paymentDate' => $request->paymentDate,
            'transactionId' => $request->transactionId,
            'transactionProof' => $request->transactionProof,
            'createdBy' => $tokenData->userId
        ]);
        if ($newPayment) {
            return response(["message" => "New Payment added successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }

    //cancel ongoing enquiry group tour
    public function cancelEnquiryGroupTour(Request $request)
    {
        $validateData = Validator::make($request->All(), [
            'enquiryGroupId' => 'required|numeric',
            'closureReason' => 'required'
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }

        //update the enquiryProcess to 3 (cancel enquiry)
        $cancelEnquiry = DB::table('enquirygrouptours')->where('enquiryGroupId', $request->enquiryGroupId)->update([
            'enquiryProcess' => 3,
            'closureReason' => $request->closureReason
        ]);
        if ($cancelEnquiry) {
            return response(["message" => "Enquiry deleted successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }

    //lost enquiries group tour
    public function lostEnquiryGroupTour(Request $request)
    {
        //cheks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $lostGroupTour = DB::table('enquirygrouptours')
            ->join('grouptours', 'enquirygrouptours.groupTourId', 'grouptours.groupTourId')
            ->where('enquiryProcess', 3)
            ->where('enquirygrouptours.createdBy', $tokenData->userId)
            ->select('enquirygrouptours.*', 'grouptours.tourName', 'grouptours.destinationId')
            ->orderBy('enquirygrouptours.enquiryGroupId', 'desc');

        // if (!empty($request->tourName)) {
        //     $search = $request->tourName;
        //     $lostGroupTour->where('grouptours.tourName', 'like', '%' . $search . '%');
        // }
        if (!empty($request->guestName)) {
            $search = $request->guestName;
            $lostGroupTour->where('enquirygrouptours.guestName', 'like', '%' . $search . '%');
        }

        $lostGroupTours = $lostGroupTour->paginate($request->PerPage == null ? 10 : $request->perPage);
        if ($lostGroupTours->isEmpty()) {
            $lostGroupArray = [];
        }
        $myObj = new \stdClass();
        foreach ($lostGroupTours as $key => $value) {
            $myObj->enqGroupId = $value->enquiryGroupId;
            $myObj->enqDate = date('d-m-Y', strtotime($value->created_at));
            $myObj->guestName = $value->guestName;
            $myObj->contact = $value->contact;
            $myObj->tourName = $value->tourName;
            $myObj->destinationId = $value->destinationId == 1 ? 'Domestic' : 'International';

            $myObj->pax = $value->adults + $value->child;
            $myObj->lastFollow =  date('d-m-Y', strtotime($value->nextFollowUp));
            $myObj->closureReason = $value->closureReason;

            $lostGroupArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $lostGroupArray,
            'total' => $lostGroupTours->total(),
            'currentPage' => $lostGroupTours->currentPage(),
            'perPage' => $lostGroupTours->perPage(),
            'nextPageUrl' => $lostGroupTours->nextPageUrl(),
            'previousPageUrl' => $lostGroupTours->previousPageUrl(),
            'lastPage' => $lostGroupTours->lastPage()
        ), 200);
    }

    //booking records
    public function bookingRecords(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $bookingRecords = DB::table('enquirygrouptours')
            ->join('grouptourdiscountdetails', 'enquirygrouptours.enquiryGroupId', '=', 'grouptourdiscountdetails.enquiryGroupId')
            ->join('grouptours', 'enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
            ->where('enquirygrouptours.enquiryProcess', 2)
            ->select('grouptours.*', 'enquirygrouptours.*', 'grouptourdiscountdetails.*', 'grouptourdiscountdetails.created_at as created_at')
            ->paginate($request->perPage == null ? 10 : $request->perPage);

        if ($bookingRecords->isEmpty()) {
            $bookingRecordsArray = [];
        }
        $myObj = new \stdClass();
        foreach ($bookingRecords as $key => $value) {
            $myObj->guestName = $value->billingName;
            $myObj->phoneNo = $value->phoneNo;
            $myObj->tourType = $value->tourTypeId;
            $myObj->tourName = $value->tourName;
            $myObj->pax = $value->adults + $value->child;
            $myObj->bookingDate = Carbon::parse($value->created_at)->toDateString();
            $myObj->travelDate = $value->startDate;
            $startDate = Carbon::parse($value->startDate);
            $endDate = Carbon::parse($value->endDate);
            $duration = $startDate->diffInDays($endDate);
            $myObj->duration = $duration;

            $bookingRecordsArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $bookingRecordsArray,
            'total' => $bookingRecords->total(),
            'currentPage' => $bookingRecords->currentPage(),
            'perPage' => $bookingRecords->perPage(),
            'nextPageUrl' => $bookingRecords->nextPageUrl(),
            'previousPageUrl' => $bookingRecords->previousPageUrl(),
            'lastPage' => $bookingRecords->lastPage()
        ), 200);
    }

    //pdf store
    public function imageUpload(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,png,jpeg|max:2048'
        ]);

        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 422);
        }

        if ($request->file('image')) {
            $file = $request->file('image');
            $filename = date('YmdHi') . $file->getClientOriginalName();
            $file->move('Image', $filename);
            $imageUrl = asset('Image/' . $filename);

            return response()->json(['message' => 'Uploaded successfully', 'image_url' => $imageUrl]);
        }
    }


    //view details group-tour
    public function viewDetailsGroupTour(Request $request)
    {
        //validator
        $validateData = Validator::make($request->all(), [
            'groupTourId' => 'required'
        ]);

        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 422);
        }

        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1, 2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $detailGroupTour = DB::table('grouptours')
            ->join('tourtype', 'grouptours.tourTypeId', '=', 'tourtype.tourTypeId')
            ->join('countries', 'grouptours.countryId', '=', 'countries.countryId')
            ->join('states', 'grouptours.stateId', '=', 'states.stateId')
            ->join('dropdowndestination', 'grouptours.destinationId', '=', 'dropdowndestination.destinationId')
            ->join('dropdowndeparturetype', 'grouptours.departureTypeId', '=', 'dropdowndeparturetype.departureTypeId')
            ->join('dropdownvehicle', 'grouptours.vehicleId', '=', 'dropdownvehicle.vehicleId')
            ->join('dropdownmealplan', 'grouptours.mealPlanId', '=', 'dropdownmealplan.mealPlanId')
            ->join('dropdownkitchen', 'grouptours.kitchenId', '=', 'dropdownkitchen.kitchenId')
            ->join('dropdownmealtype', 'grouptours.mealTypeId', '=', 'dropdownmealtype.mealTypeId')
            ->join('grouptourdetails', 'grouptours.groupTourId', '=', 'grouptourdetails.groupTourId')
            ->where('grouptours.groupTourId', $request->groupTourId)
            ->select('grouptours.*', 'countries.countryName', 'states.stateName', 'tourtype.tourTypeName', 'dropdowndestination.destinationName', 'dropdowndeparturetype.departureName', 'dropdownvehicle.vehicleName', 'dropdownmealplan.mealPlanName', 'dropdownkitchen.kitchenName', 'dropdownmealtype.mealTypeName', 'grouptourdetails.*')
            ->get();



        $skeletonItineraryData = DB::table('grouptourskeletonitinerary')
            ->where('groupTourId', $request->groupTourId)
            ->select('date', 'destination', 'overnightAt', 'hotelName', 'hotelAddress')
            ->get();

        $cityId = DB::table('grouptourscity')
            ->join('cities', 'grouptourscity.cityId', '=', 'cities.citiesId')
            ->where('groupTourId', $request->groupTourId)
            ->select('cities.citiesId', 'cities.citiesName')
            ->get();

        $tourPrice = DB::table('grouptourpricediscount')
            ->join('dropdownroomsharing', 'grouptourpricediscount.roomShareId', '=', 'dropdownroomsharing.roomShareId')
            ->where('grouptourpricediscount.groupTourId', $request->groupTourId)
            ->select('grouptourpricediscount.roomShareId', 'roomShareName', 'tourPrice', 'offerPrice')
            ->get();

        $detailedItinerary = DB::table('grouptourdetailitinerary')
            ->where('groupTourId', $request->groupTourId)
            ->get();
        foreach ($detailedItinerary as $item) {
            $item->mealTypeId = json_decode($item->mealTypeId);
        }
        $detailedItineraryMealType = DB::table('grouptourdetailitinerary')
            ->where('groupTourId', $request->groupTourId)
            ->select('mealTypeId')
            ->get();
        foreach ($detailedItineraryMealType as $item) {
            $item->mealTypeId = json_decode($item->mealTypeId);
        }
        // dd($detailedItineraryMealType);

        $trainDetails = DB::table('grouptourtrain')
            ->where('groupTourId', $request->groupTourId)
            ->get();

        $flightDetails = DB::table('grouptourflight')
            ->where('groupTourId', $request->groupTourId)
            ->get();

        $d2d = DB::table('grouptourd2dtime')
            ->where('groupTourId', $request->groupTourId)
            ->get();

        $visaDocuments = DB::table('visaDocumentsGt')
            ->where('groupTourId', $request->groupTourId)
            ->get();


        return response()->json([
            'detailGroupTour' => $detailGroupTour,
            'skeletonItinerary' => $skeletonItineraryData,
            'detailedItinerary' => $detailedItinerary,
            'detailedItineraryMealType' => $detailedItineraryMealType,
            'tourPrice' => $tourPrice,
            'trainDetails' => $trainDetails,
            'flightDetails' => $flightDetails,
            'dtod' =>  $d2d,
            'city' => $cityId,
            'visaDocuments' => $visaDocuments
        ], 200);
    }


    //dropdown rooms price group tour
    public function dropdownRoomPrice(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'enquiryGroupId' => 'required|numeric'
        ]);
        if ($validate->fails()) {
            return response()->json(['message' => $validate->errors()->all()], 422);
        }
        //listing
        $enqDetails =  DB::table('enquirygrouptours')->where('enquiryGroupId', $request->enquiryGroupId)->first();
        if (!$enqDetails) {
            return response()->json(['message' => 'Enq detail not found'], 500);
        }
        $roomPrice = DB::table('grouptourpricediscount')
            ->join('dropdownroomsharing', 'grouptourpricediscount.roomShareId', '=', 'dropdownroomsharing.roomShareId')
            ->where('grouptourpricediscount.groupTourId', $enqDetails->groupTourId)
            ->select('grouptourpricediscount.grouppricediscountId', 'grouptourpricediscount.roomShareId', 'grouptourpricediscount.tourPrice', 'grouptourpricediscount.offerPrice', 'dropdownroomsharing.roomShareName')
            ->get();
        return response()->json([
            'data' =>  $roomPrice
        ], 200);
    }


    //dropdown guestRefId
    public function dropdownGuestRefId(Request $request)
    {
        $guestIds = DB::table('enquirygrouptours')
            ->select('guestId')
            ->get(); // Remove paginate() to get all data

        $guestArray = [];

        foreach ($guestIds as $guestId) {
            $guestObject = new \stdClass();
            $guestObject->guestRefId = $guestId->guestId;
            $guestArray[] = $guestObject;
        }

        return response()->json(['data' => $guestArray], 200);
    }

    //guest-listing name from table 'groupguestdetails'
    public function listGuestsNames(Request $request)
    {
        $guestNames = DB::table('grouptourguestdetails')->get();
        $guestNamesArrayGt = [];
        foreach ($guestNames as  $value) {
            $myObj = new \stdClass();
            $myObj->groupGuestDetailId = $value->groupGuestDetailId;
            $myObj->familyHeadName = $value->familyHeadName;
            $myObj->guestId = $value->guestId;

            $guestNamesArrayGt[] = $myObj;
        }
        return response()->json([
            'data' => $guestNamesArrayGt
        ], 200);
    }

    //guest details group tours
    public function guestDetail(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'groupGuestDetailId' => 'required'
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 422);
        }

        $details = DB::table('grouptourguestdetails')->where('groupGuestDetailId', $request->groupGuestDetailId)->first();
        if ($details) {
            return response()->json([
                'familyHeadName' => $details->familyHeadName,
                'gender' => $details->gender,
                'contact' => $details->contact,
                'mailId' => $details->mailId,
                'address' => $details->address,
                'dob' => $details->dob,
                'marriageDate' => $details->marriageDate,
                'adharCard' => $details->adharCard,
                'passport' => $details->passport,
                'adharNo' => $details->adharNo,
                'pan' => $details->pan,
                'panNo' => $details->panNo,
                'guestId' => $details->guestId
            ], 200);
        }
    }

    //guest name dropdown custom tour
    public function guestNamesCt(Request $request)
    {
        $guestNamesCt = DB::table('customTourGuestDetails')->get();
        $guestNamesArrayCt = [];
        foreach ($guestNamesCt as  $value) {
            $myObj = new \stdClass();
            $myObj->customGuestDetailsId  = $value->customGuestDetailsId;
            $myObj->familyHeadName = $value->familyHeadName;
            $myObj->guestId = $value->guestId;

            $guestNamesArrayCt[] = $myObj;
        }
        return response()->json([
            'data' => $guestNamesArrayCt
        ], 200);
    }

    //guest details custom tour
    public function guestDetailCt(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'customGuestDetailsId' => 'required'
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 422);
        }

        $details = DB::table('customTourGuestDetails')->where('customGuestDetailsId', $request->customGuestDetailsId)->first();
        if ($details) {
            return response()->json([
                'familyHeadName' => $details->familyHeadName,
                'gender' => $details->gender,
                'contact' => $details->contact,
                'mailId' => $details->mailId,
                'address' => $details->address,
                'dob' => $details->dob,
                'marriageDate' => $details->marriageDate,
                'adharCard' => $details->adharCard,
                'passport' => $details->passport,
                'adharNo' => $details->adharNo,
                'pan' => $details->pan,
                'panNo' => $details->panNo,
                'guestId' => $details->guestId
            ], 200);
        }
    }
}
