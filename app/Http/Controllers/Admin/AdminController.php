<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    //admin-login
    public function adminLogin(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ]);
        if ($validateData->fails()) {
            return  response()->json(array(
                'message' => $validateData->errors()->all()
            ), 400);
        }
        $admin = User::where(['email' => $request->email, 'password' => $request->password])->first();
        if (!$admin) {
            return  response()->json(array(
                'message' => 'Admin Not Found'
            ), 404);
        }
        //check role id
        if ($admin->roleId == 1) {

            $token = rand(100000, 999999) . Carbon::now()->timestamp;
            // dd($token);
            $updateToken = DB::table('users')->where('userId', $admin->userId)->update([
                'token' => $token,
            ]);
            return response()->json([
                'message' => 'Admin logged in successfully',
                'token' => $token,
                'roleId' => $admin->roleId
            ], 200);
        } else {
            return response(["message" => "Invalid roleid"], 404);
        }
    }

    //tour type
    public function addTourType(Request $request)
    {
        //validate data
        $validateData = Validator::make($request->all(), [
            'tourTypeName' => 'required'
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }

        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        //add tour type
        $tourType = DB::table('tourtype')->insert([
            "tourTypeName" => $request->tourTypeName
        ]);
        if ($tourType) {
            return response(["message" => "Tour Type added successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }

    //edit tour type
    public function editTourType(Request $request)
    {
        //validate the data
        $validateData = Validator::make($request->all(), [
            'tourTypeId' => 'required|numeric'
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->error()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        //update tour type name
        $tourType = DB::table('tourtype')->where('tourTypeId', $request->tourTypeId)->update([
            "tourTypeName" => $request->tourTypeName
        ]);
        if ($tourType) {
            return response(["message" => "Tour Type Name Updated Successfully"], 200);
        } else {
            return response(["message" => "Something Went Wrong"], 500);
        }
    }

    //delete tour type
    public function deleteTourType(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            "tourTypeId" => "required|numeric"
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->error()->fails()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        //checks data exists in other table
        $existsIngroup = DB::table('grouptours')->where('tourTypeId', $request->tourTypeId)->exists();

        if ($existsIngroup) {
            return response()->json([
                'message' => "This Group Tour details are added in another table"
            ], 400);
        }

        //delete the tour type
        $deleteTourType = DB::table('tourtype')->where('tourTypeId', $request->tourTypeId)->delete();
        if ($deleteTourType) {
            return response(["message" => "Tour type deleted successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }

    //listing tour type
    public function tourTypeList(Request $request)
    {
        $tourType = DB::table('tourtype')->paginate();
        // dd($tourType);
        if ($tourType->isEmpty()) {
            $tourType_array = [];
        }
        $myObj = new \stdClass();
        foreach ($tourType as $key => $value) {
            $myObj->tourTypeId  = $value->tourTypeId;
            $myObj->tourTypeName = $value->tourTypeName;

            $tourType_array[] = $myObj;
            $myObj = new \stdClass();
        }
        // dd($tourType_array);
        return  response()->json(array(
            'data' => $tourType_array,
        ), 200);
    }

    //destination listing
    public function destinationList()
    {
        $destinationList = DB::table('dropdowndestination')->paginate();
        // dd($groupTours);
        if ($destinationList->isEmpty()) {
            $destination_array = [];
        }
        $myObj = new \stdClass();
        foreach ($destinationList as $key => $value) {
            $myObj->destinationId  = $value->destinationId;
            $myObj->destinationName = $value->destinationName;

            $destination_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $destination_array,
        ), 200);
    }

    //departure type listing
    public function departureTypeList(Request $request)
    {
        $departureList = DB::table('dropdowndeparturetype')->paginate();
        if ($departureList->isEmpty()) {
            $departurelist_array = [];
        }
        $myObj = new \stdClass();
        foreach ($departureList as $key => $value) {
            $myObj->departureTypeId = $value->departureTypeId;
            $myObj->departureName = $value->departureName;

            $departurelist_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $departurelist_array,
        ), 200);
    }

    //listing vehicle
    public function vehicleListing(Request $request)
    {
        $vehicleList = DB::table('dropdownvehicle')->paginate();
        if ($vehicleList->isEmpty()) {
            $vehclelist_array = [];
        }
        $myObj = new \stdClass();
        foreach ($vehicleList as $key => $value) {
            $myObj->vehicleId = $value->vehicleId;
            $myObj->vehicleName = $value->vehicleName;

            $vehiclelist_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $vehiclelist_array,
        ), 200);
    }

    //listing-meal plan
    public function mealPlanList(Request $request)
    {
        $mealPlanList = DB::table('dropdownmealplan')->paginate();
        if ($mealPlanList->isEmpty()) {
            $mealPlan_array = [];
        }
        $myObj = new \stdClass();
        foreach ($mealPlanList as $key => $value) {
            $myObj->mealPlanId = $value->mealPlanId;
            $myObj->mealPlanName = $value->mealPlanName;

            $mealPlan_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $mealPlan_array
        ), 200);
    }
    //listing kitchen
    public function kitchenList(Request $request)
    {
        $kitchenList = DB::table('dropdownkitchen')->paginate();
        // dd($kitchenList);
        if ($kitchenList->isEmpty()) {
            $kitchenList_array = [];
        }
        $myObj = new \stdClass();
        foreach ($kitchenList as $key => $value) {
            $myObj->kitchenId = $value->kitchenId;
            $myObj->kitchenName = $value->kitchenName;

            $kitchenList_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $kitchenList_array
        ), 200);
    }
    //meal-type listing
    public function mealTypeList(Request $request)
    {
        $mealTypeList = DB::table('dropdownmealtype')->paginate();
        if ($mealTypeList->isEmpty()) {
            $mealType_array = [];
        }
        $myObj = new \stdClass();
        foreach ($mealTypeList as $key => $value) {
            $myObj->mealTypeId = $value->mealTypeId;
            $myObj->mealTypeName = $value->mealTypeName;

            $mealType_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $mealType_array,
        ), 200);
    }

    //enquiry refernce list
    public function enquiryReferenceList(Request $request)
    {
        $enquiryReferList = DB::table('dropdownenquiryreference')->paginate();
        if ($enquiryReferList->isEmpty()) {
            $enquiryRefer_array = [];
        }
        $myObj = new \stdClass();
        foreach ($enquiryReferList as $key => $value) {
            $myObj->enquiryReferId = $value->enquiryReferId;
            $myObj->enquiryReferName = $value->enquiryReferName;

            $enquiryRefer_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $enquiryRefer_array
        ), 200);
    }

    //priority listing
    public function priorityList(Request $request)
    {
        $priorityList = DB::table('dropdownpriority')->paginate();
        if ($priorityList->isEmpty()) {
            $priority_array = [];
        }
        $myObj = new \stdClass();
        foreach ($priorityList as $key => $value) {
            $myObj->priorityId = $value->priorityId;
            $myObj->priorityName = $value->priorityName;

            $priority_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $priority_array
        ), 200);
    }

    //relation with family
    public function relationList()
    {
        $relationList = DB::table('dropdownrelationwithfamily')->paginate();
        if ($relationList->isEmpty()) {
            $relation_array = [];
        }
        $myObj = new \stdClass();
        foreach ($relationList as $key => $value) {
            $myObj->relationId = $value->relationId;
            $myObj->relationName = $value->relationName;

            $relation_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $relation_array
        ), 200);
    }

    //room sharing listing
    public function roomSharingList()
    {
        $roomShareList = DB::table('dropdownroomsharing')->paginate();
        if ($roomShareList->isEmpty()) {
            $roomShare_array = [];
        }
        $myObj = new \stdClass();
        foreach ($roomShareList as $key => $value) {
            $myObj->roomShareId = $value->roomShareId;
            $myObj->roomShareName = $value->roomShareName;

            $roomShare_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $roomShare_array
        ), 200);
    }
    //payment mode listing
    public function paymentModeList()
    {
        $payModeList = DB::table('dropdownpaymentmode')->paginate();
        if ($payModeList->isEmpty()) {
            $payMode_array = [];
        }
        $myObj = new \stdClass();
        foreach ($payModeList as $key => $value) {
            $myObj->paymentModeId = $value->paymentModeId;
            $myObj->paymentModeName = $value->paymentModeName;

            $payMode_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $payMode_array
        ), 200);
    }
    //online type list
    public function onlineTypeList()
    {
        $onlineTypeList = DB::table('dropdownonlinetype')->paginate();
        if ($onlineTypeList->isEmpty()) {
            $onlineType_array = [];
        }
        $myObj = new \stdClass();
        foreach ($onlineTypeList as $key => $value) {
            $myObj->onlineTypeId = $value->onlineTypeId;
            $myObj->onlineTypeName = $value->onlineTypeName;

            $onlineType_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $onlineType_array
        ), 200);
    }

    //city list dropdown
    public function dropdownCity(Request $request)
    {
        $cityList = DB::table('dropdownCities')->paginate();
        if ($cityList->isEmpty()) {
            $cityList_array = [];
        }
        $myObj = new \stdClass();
        foreach ($cityList as $key => $value) {
            $myObj->cityId   = $value->cityId;
            $myObj->cityName = $value->cityName;

            $cityList_array[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json(array(
            'data' => $cityList_array
        ), 200);
    }

    //add-group tour form
    public function addGroupTour(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'tourName' => 'required|string|max:30',
            'tourCode' => 'required|unique:grouptours',
            'tourTypeId' => 'required|numeric',
            'departureTypeId' => 'required|numeric',
            'destinationId' => 'required|numeric',
            'countryId' => 'required|numeric',
            'startDate' => 'required',
            'endDate' => 'required',
            'night' => 'required|numeric',
            'days' => 'required|numeric',
            'totalSeats' => 'required|numeric',
            'vehicleId' => 'required|numeric',
            'mealPlanId' => 'required|numeric',
            'kitchenId' => 'required|numeric',
            'mealTypeId' => 'required|numeric',
            'tourManager' => 'required|string|max:20',
            'inclusion' => 'required',
            'exclusion' => 'required',
            'note' => 'required',
            'visaDocuments' => 'required_if:destinationId,2',
            'visaFee' => 'required_if:destinationId,2',
            'visaInstruction' => 'required_if:destinationId,2',
            'visaAlerts' => 'required_if:destinationId,2',
            'insuranceDetails' => 'required_if:destinationId,2',
            'euroTrainDetails' => 'required_if:destinationId,2',
            'nriOriForDetails' => 'required_if:destinationId,2',

        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }
        $rules = [

            'skeletonInteriory.*.date' => 'required',
            'skeletonInteriory.*.destination' => 'required',
            'skeletonInteriory.*.overnightAt' => 'required',
            'skeletonInteriory.*.hotelName' => 'required',
            'skeletonInteriory.*.hotelAddress' => 'required',


            'roomsharingprice.*.roomShareId' => 'required',
            'roomsharingprice.*.tourPrice' => 'required',
            'roomsharingprice.*.offerPrice' => 'required',

            'detailIntenirary.*.date' => 'required',
            'detailIntenirary.*.title' => 'required',
            'detailIntenirary.*.description' => 'required|string',
            'detailInteniary.*.mealTypeId' => 'required',
            'detailInteniary.*.nightStayAt' => 'required|string',
            'detailInteniary.*.distance' => 'required|numeric',


            'd2dtime.*.startCity' => 'required',
            'd2dtime.*.pickUpMeet' => 'required',
            'd2dtime.*.pickUpMeetTime' => 'required',
            'd2dtime.*.arriveBefore' => 'required',
            'd2dtime.*.endCity' => 'required',
            'd2dtime.*.dropOffPoint' => 'required',
            'd2dtime.*.dropOffTime' => 'required',
            'd2dtime.*.bookAfter' => 'required',
        ];

        $validation = Validator::make($request->all(), $rules);

        if ($validation->fails()) {
            return response(["message" => $validation->errors()->all()], 400);
        }

        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        try {
            //start transaction
            DB::beginTransaction();

            $tourDetailsId = DB::table('grouptours')->insertGetId([
                'tourTypeId' => $request->tourTypeId,
                'tourName' => $request->tourName,
                'tourCode' => $request->tourCode,
                'departureTypeId' => $request->departureTypeId,
                'stateId' => $request->stateId,
                'countryId' => $request->countryId,
                'destinationId' => $request->destinationId,
                'startDate' => $request->startDate,
                'endDate' => $request->endDate,
                'night' => $request->night,
                'days' => $request->days,
                'totalSeats' => $request->totalSeats,
                'vehicleId' => $request->vehicleId,
                'mealPlanId' => $request->mealPlanId,
                'kitchenId' => $request->kitchenId,
                'mealTypeId' => $request->mealTypeId,
                'tourManager' => $request->tourManager,
            ]);
            if (!$tourDetailsId) {
                return response()->json(['message' => 'Tour details not added'], 500);
            }

            //cities
            $city = [];
            foreach ($request->cityId as $key => $value) {
                $city[] = [
                    'groupTourId' => $tourDetailsId,
                    'cityId' => $value,
                ];
            }
            if (empty($city)) {
                return response(["message" => "Insertable array is empty", "status" => true]);
            }
            $city = DB::table('grouptourscity')->insert($city);
            if (!$city) {
                return ['message' => 'Group tour  cities not added', 'status' => false];
            }

            //skeleton interiory
            $insertArray = [];
            foreach ($request->skeletonInteriory as $key => $value) {
                $insertArray[] = [
                    'groupTourId' => $tourDetailsId,
                    'date' => $value['date'],
                    'destination' => $value['destination'],
                    'overnightAt' => $value['overnightAt'],
                    'hotelName' => $value['hotelName'],
                    'hotelAddress' => $value['hotelAddress']
                ];
            }
            if (empty($insertArray)) {
                return response(["message" => "Insertable array is empty", "status" => true]);
            }
            $skeletonInterioryDetails = DB::table('grouptourskeletonitinerary')->insert($insertArray);
            if (!$skeletonInterioryDetails) {
                return response()->json(['message' => 'Group tour  details not added'], 404);
            }

            //room details price discount
            $data = [];
            if (count($request->roomsharingprice) !== 8) {
                return response()->json(['message' => 'Number of room sharing prices should be  8'], 400);
            }
            foreach ($request->roomsharingprice as $key => $value) {
                $data[] = [
                    'roomShareId' => $value['roomShareId'],
                    'groupTourId' =>  $tourDetailsId,
                    'tourPrice' => $value['tourPrice'],
                    'offerPrice' => $value['offerPrice'],
                ];
            }
            if (empty($data)) {
                return response()->json(['message' => 'Insertable array is empty'], 400);
            }
            $roomPrice = DB::table('grouptourpricediscount')->insert($data);
            if (!$roomPrice) {
                return response()->json(['message' => 'Group tour room price  details not added'], 500);
            }

            //Detailed Itinerary
            $detail = [];
            if (count($request->detailIntenirary) < $request->days) {
                return response()->json(['message' => 'Number of detailed itineraries should be at least ' . $request->days], 400);
            }
            foreach ($request->detailIntenirary as $key => $value) {
                $detail[] = [
                    'groupTourId' =>  $tourDetailsId,
                    'date' => $value['date'],
                    'title' => $value['title'],
                    'description' => $value['description'],
                    'distance' => $value['distance'],
                    'mealTypeId' => json_encode($value['mealTypeId']),
                    'nightStayAt' => $value['nightStayAt'],
                ];
            }
            //    dd($detail);
            if (empty($detail)) {
                return response()->json(['message' => 'Detail array is empty', 400]);
            }
            $groupDetailIntirary = DB::table('grouptourdetailitinerary')->insert($detail);
            //    dd($groupDetailIntirary);
            if (!$groupDetailIntirary) {
                return response()->json(['message' => 'Group tour room price  details not added'], 500);
            }

            //train details

            if (!empty($request->traindetails)) {
                $validationTrainDetails = Validator::make($request->traindetails, [
                    'traindetails.*.journey' => 'required',
                    'traindetails.*.trainNo' => 'required',
                    'traindetails.*.trainName' => 'required',
                    'traindetails.*.from' => 'required',
                    'traindetails.*.fromDate' => 'required',
                    'traindetails.*.fromTime' => 'required',
                    'traindetails.*.to' => 'required',
                    'traindetails.*.toDate' => 'required',
                    'traindetails.*.toTime' => 'required',
                ]);

                if ($validationTrainDetails->fails()) {
                    return response(["message" => $validationTrainDetails->errors()->all()], 400);
                }
                $train = [];
                foreach ($request->traindetails as $key => $value) {
                    $train[] = [
                        'groupTourId' =>  $tourDetailsId,
                        'journey' => $value['journey'],
                        'trainNo' => $value['trainNo'],
                        'trainName' => $value['trainName'],
                        'from' => $value['from'],
                        'fromDate' => $value['fromDate'],
                        'fromTime' => $value['fromTime'],
                        'to' => $value['to'],
                        'toDate' => $value['toDate'],
                        'toTime' => $value['toTime'],
                    ];
                }
                $trainDetails = DB::table('grouptourtrain')->insert($train);
                if (!$trainDetails) {
                    return response()->json(['message' => 'Group tour train details not added'], 500);
                }
            }

            //flight details
            if (!empty($request->flightdetails)) {
                $validationFlightDetails = Validator::make($request->traindetails, [
                    'flightdetails.*.journey' => 'required',
                    'flightdetails.*.flight' => 'required',
                    'flightdetails.*.airline' => 'required',
                    'flightdetails.*.class' => 'required',
                    'flightdetails.*.from' => 'required',
                    'flightdetails.*.fromDate' => 'required',
                    'flightdetails.*.fromTime' => 'required',
                    'flightdetails.*.to' => 'required',
                    'flightdetails.*.toDate' => 'required',
                    'flightdetails.*.toTime' => 'required',
                ]);

                if ($validationFlightDetails->fails()) {
                    return response(["message" => $validationFlightDetails->errors()->all()], 400);
                }
                $flight = [];
                foreach ($request->flightdetails as $key => $value) {
                    $flight[] = [
                        'groupTourId' =>  $tourDetailsId,
                        'journey' => $value['journey'],
                        'flight' => $value['flight'],
                        'airline' => $value['airline'],
                        'class' => $value['class'],
                        'from' => $value['from'],
                        'fromDate' => $value['fromDate'],
                        'fromTime' => $value['fromTime'],
                        'to' => $value['to'],
                        'toDate' => $value['toDate'],
                        'toTime' => $value['toTime'],
                    ];
                }
                $flightDetails = DB::table('grouptourflight')->insert($flight);
                if (!$flightDetails) {
                    return response()->json(['message' => 'Group tour flight details not added'], 500);
                }
            }



            //suggested timing for d2d
            $item = [];
            foreach ($request->d2dtime as $key => $value) {
                $item[] = [
                    'groupTourId' =>  $tourDetailsId,
                    'startCity' => $value['startCity'],
                    'pickUpMeet' => $value['pickUpMeet'],
                    'pickUpMeetTime' => $value['pickUpMeetTime'],
                    'arriveBefore' => $value['arriveBefore'],
                    'endCity' => $value['endCity'],
                    'dropOffPoint' => $value['dropOffPoint'],
                    'dropOffTime' => $value['dropOffTime'],
                    'bookAfter' => $value['bookAfter'],
                ];
            }

            $d2details = DB::table('grouptourd2dtime')->insert($item);
            if (!$d2details) {
                return response()->json(['message' => 'D2D details not added'], 500);
            }

            if ($request->destinationId == 2) {

                //visa documents
                $visaDetails = DB::table('visaDocumentsGt')->insert([
                    'groupTourId' => $tourDetailsId,
                    'visaDocuments' => $request->visaDocuments,
                    'visaFee' => $request->visaFee,
                    'visaInstruction' => $request->visaInstruction,
                    'visaAlerts' => $request->visaAlerts,
                    'insuranceDetails' => $request->insuranceDetails,
                    'euroTrainDetails' => $request->euroTrainDetails,
                    'nriOriForDetails' => $request->nriOriForDetails
                ]);
                if (!$visaDetails) {
                    return response()->json(['message' => 'Visa documents not added'], 500);
                }
            }

            $details = DB::table('grouptourdetails')->insert([
                'groupTourId' => $tourDetailsId,
                'inclusion' => $request->inclusion,
                'exclusion' => $request->exclusion,
                'note' =>  $request->note,
            ]);

            DB::commit();

            return response()->json(['message' => 'Group tour  details added successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    //group-tour listing
    public function groupTourList(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        //get group tour listing
        $groupTours = DB::table('grouptours')
            ->join('tourtype', 'grouptours.tourTypeId', '=', 'tourtype.tourTypeId')
            ->join('dropdowndestination', 'grouptours.destinationId', '=', 'dropdowndestination.destinationId')
            ->select('grouptours.*', 'tourtype.*', 'dropdowndestination.destinationName')
            ->orderBy('grouptours.groupTourId', 'desc');


        //searching by tourName
        if ($request->startDate != '' && $request->endDate != '') {
            $start_datetime = Carbon::parse($request->startDate)->startOfDay();
            $end_datetime = Carbon::parse($request->endDate)->endOfDay();

            $groupTours->where(function ($query) use ($start_datetime, $end_datetime) {
                $query->where('startDate', '>=', $start_datetime)
                    ->where('endDate', '<=', $end_datetime);
            });
        } elseif ($request->startDate != '') {
            $start_datetime = Carbon::parse($request->startDate)->startOfDay();
            $groupTours->where('startDate', '>=', $start_datetime);
        } elseif ($request->endDate != '') {
            $end_datetime = Carbon::parse($request->endDate)->endOfDay();
            $groupTours->where('endDate', '<=', $end_datetime);
        } elseif (!empty($request->search)) {
            $search = $request->search;
            $groupTours->where('tourName', 'like', '%' . $search . '%');
        }


        $groupToursDetails = $groupTours->paginate($request->perPage == null ? 10 : $request->perPage);
        // dd($groupToursDetails);
        if ($groupToursDetails->isEmpty()) {
            $groupTours_array = [];
        }



        foreach ($groupToursDetails as $value) {
            $myObj = new \stdClass();
            $myObj->groupTourId = $value->groupTourId;
            $myObj->tourName = $value->tourName;
            $myObj->tourCode = $value->tourCode;
            $myObj->destination = $value->destinationName;
            // $myObj->city = $value->departureCity;
            $myObj->startDate = date('d-m-y', strtotime($value->startDate));
            $myObj->endDate = date('d-m-y', strtotime($value->endDate));
            $myObj->duration = $value->night + $value->days;
            $groupTours_array[] = $myObj;
        }

        return response()->json([
            'data' => $groupTours_array,
            'total' => $groupToursDetails->total(),
            'currentPage' => $groupToursDetails->currentPage(),
            'perPage' => $groupToursDetails->perPage(),
            'nextPageUrl' => $groupToursDetails->nextPageUrl(),
            'previousPageUrl' => $groupToursDetails->previousPageUrl(),
            'lastPage' => $groupToursDetails->lastPage()
        ], 200);
        // $myObj = new \stdClass();
        // foreach ($groupToursDetails as $key => $value) {
        //     $myObj->groupTourId  = $value->groupTourId ;
        //     $myObj->tourName = $value->tourName;
        //     $myObj->tourCode = $value->tourCode;
        //     $myObj->destination = $value->destinationName;
        //     $myObj->departureCity = $value->departureCity;
        //     $myObj->startDate = $value->startDate;
        //     $myObj->endDate = $value->endDate;
        //     $myObj->duration = $value->night . 'N-' . $value->days . 'D';

        //     $groupTours_array[] = $myObj;
        //     $myObj = new \stdClass();
        // }
        // return  response()->json(array(
        //     'data' => $groupTours_array,
        //     'total' => $groupToursDetails->total(),
        //     'currentPage' => $groupToursDetails->currentPage(),
        //     'perPage' => $groupToursDetails->perPage(),
        //     'nextPageUrl' => $groupToursDetails->nextPageUrl(),
        //     'previousPageUrl' => $groupToursDetails->previousPageUrl(),
        //     'lastPage' => $groupToursDetails->lastPage()
        // ), 200);
    }

    //edit group-tour listing
    public function editGroupTourListing(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            // 'groupTourId' => 'required|numeric',
            // 'tourName' => 'required',
            // 'tourCode' => 'required',
            // 'tourTypeId' => 'required',
            // 'departureTypeId' => 'required',
            // 'departureCity' => 'required',
            // 'destinationId' => 'required',
            // 'startDate' => 'required',
            // 'endDate' => 'required',
            // 'night' => 'required',
            // 'days' => 'required',
            // 'cityId' => 'required',
            // 'totalSeats' => 'required',
            // 'vehicleId' => 'required',
            // 'mealPlanId' => 'required',
            // 'kitchenId' => 'required',
            // 'mealTypeId' => 'required',
            // 'tourManager' => 'required'
        ]);
        if ($validateData->fails()) {
            return response(["message" => $validateData->errors()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        try {
            //start transaction
            DB::beginTransaction();
            $existsInEnquiry = DB::table('enquirygrouptours')->where('groupTourId', $request->groupTourId)->exists();
            if ($existsInEnquiry) {
                return response()->json(['message' => 'Group tour ID exists in enquiryGroupTours. Update not allowed.'], 400);
            } else {
                $tourDetails = DB::table('grouptours')->where('groupTourId', $request->groupTourId)->update([
                    'tourTypeId' => $request->tourTypeId,
                    'tourName' => $request->tourName,
                    'tourCode' => $request->tourCode,
                    'departureTypeId' => $request->departureTypeId,
                    'stateId' => $request->stateId,
                    'countryId' => $request->countryId,
                    'destinationId' => $request->destinationId,
                    'startDate' => $request->startDate,
                    'endDate' => $request->endDate,
                    'night' => $request->night,
                    'days' => $request->days,
                    'totalSeats' => $request->totalSeats,
                    'vehicleId' => $request->vehicleId,
                    'mealPlanId' => $request->mealPlanId,
                    'kitchenId' => $request->kitchenId,
                    'mealTypeId' => $request->mealTypeId,
                    'tourManager' => $request->tourManager,

                ]);

                //city
                DB::table('grouptourscity')
                    ->where('groupTourId', $request->groupTourId)
                    ->delete();

                $cityDetail = true;
                foreach ($request->cityId as $value) {

                    $city = [
                        'groupTourId' => $request->groupTourId,
                        'cityId' => $value,
                    ];
                    $cityDetail = DB::table('grouptourscity')
                        ->insert($city);
                }

                if (!$cityDetail) {
                    return response(['message' => 'Group tour city not added'], 422);
                }

                //skeleton interiory
                DB::table('grouptourskeletonitinerary')
                    ->where('groupTourId', $request->groupTourId)
                    ->delete();
                $skeletonInterioryDetails = true;

                foreach ($request->skeletonInteriory as $value) {

                    $updateArray = [
                        'groupTourId' => $request->groupTourId,
                        'date' => $value['date'],
                        'destination' => $value['destination'],
                        'overnightAt' => $value['overnightAt'],
                        'hotelName' => $value['hotelName'],
                        'hotelAddress' => $value['hotelAddress'],
                    ];

                    $skeletonInterioryDetails = DB::table('grouptourskeletonitinerary')->insert($updateArray);
                }
                if (!$skeletonInterioryDetails) {
                    return response()->json(['message' => 'Group tour details not added'], 422);
                }
                //detailedItinerary
                DB::table('grouptourdetailitinerary')->where('groupTourId', $request->groupTourId)->delete();
                $detailedItinerary = true;
                if (count($request->detailedItinerary) < $request->days) {
                    return response()->json(['message' => 'Number of detailed itineraries should be at least ' . $request->days], 400);
                }

                foreach ($request->detailedItinerary as $value) {
                    $updatedetailArray = [
                        'groupTourId' => $request->groupTourId,
                        'date' => $value['date'],
                        'title' => $value['title'],
                        'description' => $value['description'],
                        'distance' => $value['distance'],
                        'nightStayAt' => $value['nightStayAt'],
                        'mealTypeId' => json_encode($value['mealTypeId'])
                    ];

                    $detailedItinerary = DB::table('grouptourdetailitinerary')->insert($updatedetailArray);
                }
                if (!$detailedItinerary) {
                    return response()->json(['message' => 'Group tour details not added'], 422);
                }

                //room details price discount
                DB::table('grouptourpricediscount')->where('groupTourId', $request->groupTourId)->delete();
                $roomDetails = true;
                if (count($request->roomsharingprice) !== 8) {
                    return response()->json(['message' => 'Number of room sharing prices should be  8'], 400);
                }
                foreach ($request->roomsharingprice as $key => $value) {
                    $data = [
                        'groupTourId' => $request->groupTourId,
                        'roomShareId' => $value['roomShareId'],
                        'tourPrice' => $value['tourPrice'],
                        'offerPrice' => $value['offerPrice'],
                    ];
                    $datas[] = $data;
                }

                $roomDetails = DB::table('grouptourpricediscount')->insert($datas);
                if (!$roomDetails) {
                    return response()->json(['message' => 'Group tour room price  details not added'], 422);
                }

                //train details
                if (!empty($request->traindetail)) {
                    DB::table('grouptourtrain')->where('groupTourId', $request->groupTourId)->delete();
                    $trainDetails = true;
                    foreach ($request->traindetail as $key => $value) {
                        $train = [
                            'groupTourId' => $request->groupTourId,
                            'journey' => $value['journey'],
                            'trainNo' => $value['trainNo'],
                            'trainName' => $value['trainName'],
                            'from' => $value['from'],
                            'fromDate' => $value['fromDate'],
                            'fromTime' => $value['fromTime'],
                            'to' => $value['to'],
                            'toDate' => $value['toDate'],
                            'toTime' => $value['toTime'],
                        ];
                        $trains[] = $train;
                    }

                    $trainDetails = DB::table('grouptourtrain')->insert($trains);
                    if (!$trainDetails) {
                        return response()->json(['message' => 'Group tour train details not updated'], 422);
                    }
                }

                //flight details
                if (!empty($request->flightdetail)) {
                    DB::table('grouptourflight')->where('groupTourId', $request->groupTourId)->delete();
                    $flightDetails = true;
                    foreach ($request->flightdetail as $key => $value) {
                        $flight = [
                            'groupTourId' => $request->groupTourId,
                            'journey' => $value['journey'],
                            'flight' => $value['flight'],
                            'airline' => $value['airline'],
                            'class' => $value['class'],
                            'from' => $value['from'],
                            'fromDate' => $value['fromDate'],
                            'fromTime' => $value['fromTime'],
                            'to' => $value['to'],
                            'toDate' => $value['toDate'],
                            'toTime' => $value['toTime'],
                        ];
                        $flights[] = $flight;
                    }

                    $flightDetails = DB::table('grouptourflight')->insert($flights);
                    if (!$flightDetails) {
                        return response()->json(['message' => 'Group tour flight details not added'], 422);
                    }
                }

                //suggested timing for d2d
                DB::table('grouptourd2dtime')->where('groupTourId', $request->groupTourId)->delete();

                $dtod = true;
                foreach ($request->d2dtime as $key => $value) {
                    $item = [
                        'groupTourId' => $request->groupTourId,
                        'startCity' => $value['startCity'],
                        'pickUpMeet' => $value['pickUpMeet'],
                        'pickUpMeetTime' => $value['pickUpMeetTime'],
                        'arriveBefore' => $value['arriveBefore'],
                        'endCity' => $value['endCity'],
                        'dropOffPoint' => $value['dropOffPoint'],
                        'dropOffTime' => $value['dropOffTime'],
                        'bookAfter' => $value['bookAfter'],
                    ];
                }


                $dtod = DB::table('grouptourd2dtime')->insert($item);
                if (!$dtod) {
                    return response()->json(['message' => 'D2D details not updated'], 422);
                }
                //detailed inclusion
                $details = DB::table('grouptourdetails')->where('groupTourId', $request->groupTourId)->update([
                    'inclusion' => $request->inclusion,
                    'exclusion' => $request->exclusion,
                    'note' =>  $request->note

                ]);
                //visa documents details
                $visaDetails = DB::table('visaDocumentsGt')->where('groupTourId', $request->groupTourId)->update([
                    'visaDocuments' => $request->visaDocuments,
                    'visaFee' => $request->visaFee,
                    'visaInstruction' => $request->visaInstruction,
                    'visaAlerts' => $request->visaAlerts,
                    'insuranceDetails' => $request->insuranceDetails,
                    'euroTrainDetails' => $request->euroTrainDetails,
                    'nriOriForDetails' => $request->nriOriForDetails
                ]);


                DB::commit();

                return response()->json(['message' => 'Group tour  details updated successfully'], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    //delete group-tour listing
    public function deleteGroupTourList(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'groupTourId' => 'required|numeric'
        ]);
        if ($validateData->fails()) {
            return response()->json(array(
                'message' => $validateData->errors()->all()
            ), 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $existsInEnquiryGroupTours = DB::table('enquirygrouptours')->where('groupTourId', $request->groupTourId)->exists();
        $existsInTrains = DB::table('grouptourtrain')->where('groupTourId', $request->groupTourId)->exists();
        $existsInFlight = DB::table('grouptourflight')->where('groupTourId', $request->groupTourId)->exists();
        $existsInd2d = DB::table('grouptourd2dtime')->where('groupTourId', $request->groupTourId)->exists();
        $existsinitiary = DB::table('grouptourdetailitinerary')->where('groupTourId', $request->groupTourId)->exists();
        $existsenq = DB::table('grouptourenquirydetails')->where('groupTourId', $request->groupTourId)->exists();
        $existsdis = DB::table('grouptourpricediscount')->where('groupTourId', $request->groupTourId)->exists();
        $existsSkeleton = DB::table('grouptourskeletonitinerary')->where('groupTourId', $request->groupTourId)->exists();
        $existsVisa = DB::table('visaDocumentsGt')->where('groupTourId', $request->groupTourId)->exists();
        $deleteGroupTour = DB::table('grouptours')->where('groupTourId', $request->groupTourId)->exists();

        if ($existsInEnquiryGroupTours) {
            return response()->json([
                'message' => "This Group Tour details are added in enquiry table"
            ], 409);
        }
        if ($existsInTrains) {
            DB::table('grouptourtrain')->where('groupTourId', $request->groupTourId)->delete();
        }
        if ($existsInFlight) {
            DB::table('grouptourflight')->where('groupTourId', $request->groupTourId)->delete();
        }
        if ($existsInd2d) {
            DB::table('grouptourd2dtime')->where('groupTourId', $request->groupTourId)->delete();
        }
        if ($existsinitiary) {
            DB::table('grouptourdetailitinerary')->where('groupTourId', $request->groupTourId)->delete();
        }
        if ($existsenq) {
            DB::table('grouptourenquirydetails')->where('groupTourId', $request->groupTourId)->delete();
        }
        if ($existsdis) {
            DB::table('grouptourpricediscount')->where('groupTourId', $request->groupTourId)->delete();
        }
        if ($existsSkeleton) {
            DB::table('grouptourskeletonitinerary')->where('groupTourId', $request->groupTourId)->delete();
        }
        if ($existsVisa) {
            DB::table('visaDocumentsGt')->where('groupTourId', $request->groupTourId)->delete();
        }


        if ($deleteGroupTour) {

            DB::table('grouptours')->where('groupTourId', $request->groupTourId)->delete();

            return response()->json(['message' => 'Group tours deleted successfully'], 200);
        } else {

            return response()->json(['message' => 'Group tour record not found'], 404);
        }
    }

    //sales executive listing seraching name
    public function salesListing(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $sales = User::where('roleId', 2);
        //searching by salesName
        if (!empty($request->search) || $request->search != "" || $request->search != null) {
            $search = $request->search;
            $sales->where(function ($q) use ($search) {
                $q->where('userName', 'like', '%' . $search . '%');
            });
        }

        $salesDetails = $sales->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($salesDetails->isEmpty()) {
            $salesArray = [];
        }
        $myObj = new \stdClass();
        foreach ($salesDetails as $key => $value) {
            $myObj->userId = $value->userId;
            $myObj->userName = $value->userName;

            $salesArray[] = $myObj;
            $myObj = new \stdClass();
        }

        return  response()->json(array(
            'data' => $salesArray,
            'total' => $salesDetails->total(),
            'currentPage' => $salesDetails->currentPage(),
            'perPage' => $salesDetails->perPage(),
            'nextPageUrl' => $salesDetails->nextPageUrl(),
            'previousPageUrl' => $salesDetails->previousPageUrl(),
            'lastPage' => $salesDetails->lastPage()
        ), 200);
    }

    //dropdown years
    public function dropdownYears(Request $request)
    {
        $years = DB::table('dropdownYears')
            ->paginate();
        if ($years->isEmpty()) {
            $yearsArray = [];
        }
        $myObj = new \stdClass();
        foreach ($years as  $value) {
            $myObj->yearId = $value->yearId;
            $myObj->year = $value->year;

            $yearsArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'data' => $yearsArray
        ], 200);
    }
    //dropdown months
    public function dropdownMonths(Request $request)
    {
        $months = DB::table('dropdownMonths')->paginate();
        if ($months->isEmpty()) {
            $monthsArray = [];
        }
        $myObj = new \stdClass();
        foreach ($months as  $value) {
            $myObj->monthId = $value->monthId;
            $myObj->monthName = $value->monthName;

            $monthsArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'data' => $monthsArray
        ], 200);
    }

    // add sales target
    public function salesTargetGt(Request $request)
    {
        // dd("here");
        $validateData = Validator::make($request->all(), [
            // "userId" => 'required',
            // "monthId" => "required",
            // "target" => "required",
            // "yearId" => "required",
            // 'quarter' => 'required',
            // "tourType" => "required"
        ]);
        if ($validateData->fails()) {
            return response()->json(["message" => $validateData->errors()->all()], 400);
        }
        //checks the token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        // dd("herert");
        //insert the data in salesTarget table
        $target = [];

        foreach ($request->targetArrayGt as $key => $value) {
            $target[] = [
                'userId' => $value['userId'],
                'monthId' => $value['monthId'],
                'target' => $value['target'],
                'yearId' => $value['yearId'],
                'tourType' => $value['tourType'],
                'quarterId' => $value['quarterId']
            ];
        }
        if (empty($target)) {
            return ['message' => 'Target array is empty'];
        }

        $targetDetails = DB::table('salesTraget')->insert($target);
        if (!$targetDetails) {
            return ['message' => 'Sales target not added', 'status' => false];
        }
        return response()->json(['message' => 'Traget added successfully'], 200);
    }

    //listing sales target
    public function salesTargetList(Request $request)
    {
        //checks the token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(["message" => "Invalid token"], 408);
        }

        //listing
        $listSalesTarget = DB::table('salesTraget')
            ->join('users', 'salesTraget.userId', '=', 'users.userId')
            ->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($listSalesTarget->isEmpty()) {
            $salesTargetArray = [];
        }
        $myObj = new \stdClass();
        foreach ($listSalesTarget as $value) {
            $myObj->salesTargetId = $value->salesTargetId;
            $myObj->userName = $value->userName;
            $myObj->month = $value->month;
            $myObj->target = $value->target;
            $myObj->year = $value->year;
            $myObj->quarter = $value->quarter;
            $myObj->tourType = $value->tourType;

            $salesTargetArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $salesTargetArray,
            'total' => $listSalesTarget->total(),
            'currentPage' => $listSalesTarget->currentPage(),
            'perPage' => $listSalesTarget->perPage(),
            'nextPageUrl' => $listSalesTarget->nextPageUrl(),
            'previousPageUrl' => $listSalesTarget->previousPageUrl(),
            'lastPage' => $listSalesTarget->lastPage()
        ), 200);
    }

    //customize tour lists
    public function ctTourLists(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        //listing
        $ctLists = DB::table('enquiryCustomTours')
            ->join('dropdowndestination', 'enquiryCustomTours.destinationId', '=', 'dropdowndestination.destinationId')
            ->where('enquiryProcess', 2)
            ->select('enquiryCustomTours.*', 'dropdowndestination.destinationName')
            ->paginate($request->perPage == null ? 10 : $request->perPage);

        if ($ctLists->isEmpty()) {
            $ctListArray = [];
        }
        $myObj = new \stdClass();
        foreach ($ctLists as  $value) {
            $myObj->enquiryCustomId = $value->enquiryCustomId;
            $myObj->groupName = $value->groupName;
            $myObj->destinationName = $value->destinationName;
            $myObj->startDate = $value->startDate;
            $myObj->endDate = $value->endDate;
            $myObj->duration = $value->nights + $value->days;

            $ctListArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $ctListArray,
            'total' => $ctLists->total(),
            'currentPage' => $ctLists->currentPage(),
            'perPage' => $ctLists->perPage(),
            'nextPageUrl' => $ctLists->nextPageUrl(),
            'previousPageUrl' => $ctLists->previousPageUrl(),
            'lastPage' => $ctLists->lastPage()
        ), 200);
    }


    //dropdown departure state
    // public function dropdownDepartState(Request $request){
    //     $states = DB::table('dropdownDepartState')->paginate($request->perPage == null ? 10 : $request->perPage);
    //     if($states->isEmpty()){
    //         $statesArray = [];
    //     }
    //     $myObj = new \stdClass();
    //     foreach ($states as  $value) {
    //         $myObj->departStateId = $value->departStateId ;
    //         $myObj->departStateName = $value->departStateName;

    //         $statesArray[] = $myObj;
    //         $myObj = new \stdClass();
    //     }
    //     return response()->json([
    //         'data' => $statesArray
    //     ], 200);
    // }

    //dropdown state country group tour
    public function ddCountryState(Request $request)
    {
        $destinationId = $request->destinationId;
        if ($destinationId == 1) {
            $states = DB::table('dropdownDepartState')->paginate();
            if ($states->isEmpty()) {
                $statesArray = [];
            }
            $myObj = new \stdClass();
            foreach ($states as  $value) {
                $myObj->departStateId = $value->departStateId;
                $myObj->departStateName = $value->departStateName;
                $myObj->departureCity = $request->departureCity;


                $statesArray[] = $myObj;
                $myObj = new \stdClass();
            }
            return response()->json([
                'data' => $statesArray
            ], 200);
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


    //add sales
    public function addSales(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'userName' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'roleId' => 'required',
            'phone' => 'required|digits:10',
            'address' => 'required',
            'businessName' => 'required',
            'establishmentType' => 'required',
            'businessAddress' => 'required',
            'accountNo' => 'required'
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $sales = DB::table('users')->insert([
            'userName' => $request->userName,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            "roleId" => $request->roleId,
            'phone' => $request->phone,
            'token' =>  rand(100000, 999999) . Carbon::now()->timestamp,
            'address' => $request->address,
            'businessName' => $request->businessName,
            'establishmentType' => $request->establishmentType,
            'businessAddress' => $request->businessAddress,
            'accountNo' => $request->accountNo
        ]);
        if ($sales) {
            return response()->json(['message' => 'Sales added successfully'], 200);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    //dropdown continents
    public function dropdownContinents()
    {
        $continents = DB::table('continents')->paginate();
        if ($continents->isEmpty()) {
            $continentsArray = [];
        }
        $myObj = new \stdClass();
        foreach ($continents as $key => $value) {
            $myObj->continentId = $value->continentId;
            $myObj->continentName = $value->continentName;

            $continentsArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return  response()->json(array(
            'data' => $continentsArray,
            'total' => $continents->total(),
            'currentPage' => $continents->currentPage(),
            'perPage' => $continents->perPage(),
            'nextPageUrl' => $continents->nextPageUrl(),
            'previousPageUrl' => $continents->previousPageUrl(),
            'lastPage' => $continents->lastPage()
        ), 200);
    }

    //add country state and city
    public function addCountry(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'continentId' => 'required',
            'countryName' => 'required'
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $country = DB::table('countries')->insert([
            'continentId' => $request->continentId,
            'countryName' => $request->countryName,
        ]);
        if ($country) {
            return response()->json(['message' => 'Country added successfully'], 200);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    //dropdown country
    public function country(Request $request)
    {
        if ($request->destinationId == 1) {
            $countries = DB::table('countries')->where('countryId', 1)
                ->join('continents', 'countries.continentId', '=', 'continents.continentId')
                ->get();
        } else {
            $countries = DB::table('countries')->where('countryId', '!=', 1)
                ->join('continents', 'countries.continentId', '=', 'continents.continentId')
                ->get();
        }

        if ($countries->isEmpty()) {
            $countriesArray = [];
        }
        $myObj = new \stdClass();
        foreach ($countries as $key => $value) {
            $myObj->countryId = $value->countryId;
            $myObj->continentId = $value->continentId;
            $myObj->countryName = $value->countryName;
            $myObj->continentName = $value->continentName;


            $countriesArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'message' => $countriesArray
        ], 200);
    }

    public function allCountryList(Request $request)
    {
        $countries = DB::table('countries')
            ->join('continents', 'countries.continentId', '=', 'continents.continentId')
            ->paginate(10);
        if ($countries->isEmpty()) {
            $countriesArray = [];
        }
        $myObj = new \stdClass();
        foreach ($countries as $key => $value) {
            $myObj->countryId = $value->countryId;
            $myObj->continentId = $value->continentId;
            $myObj->countryName = $value->countryName;
            $myObj->continentName = $value->continentName;


            $countriesArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'data' => $countriesArray,
            'total' => $countries->total(),
            'currentPage' => $countries->currentPage(),
            'perPage' => $countries->perPage(),
            'nextPageUrl' => $countries->nextPageUrl(),
            'previousPageUrl' => $countries->previousPageUrl(),
            'lastPage' => $countries->lastPage()
        ], 200);
    }

    public function continentCountryList(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'continentId' => 'required',
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }

        $countries = DB::table('countries')
            ->join('continents', 'countries.continentId', '=', 'continents.continentId')
            ->where('continents.continentId', $request->continentId)
            ->get();
        if ($countries->isEmpty()) {
            $countriesArray = [];
        }
        $myObj = new \stdClass();
        foreach ($countries as $key => $value) {
            $myObj->countryId = $value->countryId;
            $myObj->continentId = $value->continentId;
            $myObj->countryName = $value->countryName;
            $myObj->continentName = $value->continentName;


            $countriesArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'message' => $countriesArray
        ], 200);
    }

    public function continentCountryStateList(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'continentId' => 'required',
            'countryId' => 'required',

        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }

        $states = DB::table('states')
            ->where('continentId', $request->continentId)
            ->where('countryId', $request->countryId)
            ->get();

        if ($states->isEmpty()) {
            return response()->json([
                'message' => []
            ], 200);
        }
        $myObj = new \stdClass();
        foreach ($states as $key => $value) {
            $myObj->countryId = $value->countryId;
            $myObj->continentId = $value->continentId;
            $myObj->stateName = $value->stateName;
            $myObj->stateId  = $value->stateId;


            $countriesArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'message' => $countriesArray
        ], 200);
    }
    //add state
    public function addState(Request $request)
    {

        $validateData = Validator::make($request->all(), [
            'countryId' => 'required',
            'continentId' => 'required',
            'stateName' => 'required'
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $check = DB::table('countries')
            ->where('continentId', $request->continentId)->where('countryId', $request->countryId)
            ->first();
        if (!$check) {
            return response()->json(['message' => 'Contient and country not matched'], 404);
        }

        //add state
        $states = DB::table('states')->insert([
            'countryId' => $request->countryId,
            'continentId' => $request->continentId,
            'stateName' => $request->stateName,
        ]);
        if ($states) {
            return response()->json(['message' => 'States added successfully'], 200);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    //state list
    public function stateList(Request $request)
    {
        if ($request->countryId) {
            $states = DB::table('states')->where('countryId', $request->countryId)->get();
        } else {
            $states = DB::table('states')->get();
        }
        if ($states->isEmpty()) {
            $stateArray = [];
        }
        $myObj = new \stdClass();
        foreach ($states as  $value) {
            $myObj->stateId = $value->stateId;
            $myObj->countryId = $value->countryId;
            $myObj->stateName = $value->stateName;

            $stateArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'data' => $stateArray
        ], 200);
    }

    public function allStateList(Request $request)
    {
        $states = DB::table('states')
            ->join('continents', 'states.continentId', '=', 'continents.continentId')
            ->join('countries', 'states.countryId', '=', 'countries.countryId')
            ->select('states.*', 'countries.countryName', 'continents.continentName')
            ->paginate(10);
        if ($states->isEmpty()) {
            $stateArray = [];
        }
        $myObj = new \stdClass();
        foreach ($states as  $value) {
            $myObj->stateId = $value->stateId;
            $myObj->stateName = $value->stateName;
            $myObj->countryName = $value->countryName;
            $myObj->continentName = $value->continentName;
            $stateArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'data' => $stateArray,
            'total' => $states->total(),
            'currentPage' => $states->currentPage(),
            'perPage' => $states->perPage(),
            'nextPageUrl' => $states->nextPageUrl(),
            'previousPageUrl' => $states->previousPageUrl(),
            'lastPage' => $states->lastPage()
        ], 200);
    }

    public function deleteCountry(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'countryId' => 'required',
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }

        //checks the token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $check = DB::table('countries')
            ->where('countryId', $request->countryId)
            ->first();
        if (!$check) {
            return response()->json(['message' => 'country not found'], 404);
        }


        $checkCity = DB::table('cities')
            ->where('countryId', $request->countryId)
            ->first();
        if ($checkCity) {
            return response()->json(['message' => 'country stored in city so cant delete it'], 404);
        }

        $checkState = DB::table('states')
            ->where('countryId', $request->countryId)
            ->first();
        if ($checkState) {
            return response()->json(['message' => 'country stored in states so cant delete it'], 404);
        }


        //delete the country
        $deleteCountry = DB::table('countries')->where('countryId', $request->countryId)->delete();
        if ($deleteCountry) {
            return response(["message" => "country deleted successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }

    public function editCountry(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'countryId' => 'required',
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }

        //checks the token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $check = DB::table('countries')
            ->where('countryId', $request->countryId)
            ->first();
        if (!$check) {
            return response()->json(['message' => 'country not found'], 404);
        }


        $checkCity = DB::table('cities')
            ->where('countryId', $request->countryId)
            ->first();
        if ($checkCity) {
            return response()->json(['message' => 'country stored in city so cant delete it'], 404);
        }

        $checkState = DB::table('states')
            ->where('countryId', $request->countryId)
            ->first();
        if ($checkState) {
            return response()->json(['message' => 'country stored in states so cant delete it'], 404);
        }


        //delete the country
        $deleteCountry = DB::table('countries')->where('countryId', $request->countryId)->delete();
        if ($deleteCountry) {
            return response(["message" => "country deleted successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }
    public function deleteState(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'stateId' => 'required',
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }

        //checks the token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $check = DB::table('states')
            ->where('stateId', $request->stateId)
            ->first();
        if (!$check) {
            return response()->json(['message' => 'state not found'], 404);
        }


        $checkCity = DB::table('cities')
            ->where('stateId', $request->stateId)
            ->first();
        if ($checkCity) {
            return response()->json(['message' => 'state stored in city so cant delete it'], 404);
        }


        //delete the country
        $deleteCountry = DB::table('states')
            ->where('stateId', $request->stateId)
            ->delete();
        if ($deleteCountry) {
            return response(["message" => "state deleted successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }

    public function deleteCity(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'citiesId' => 'required',
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }

        //checks the token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        $check = DB::table('cities')
            ->where('citiesId', $request->citiesId)
            ->first();
        if (!$check) {
            return response()->json(['message' => 'cities not found'], 404);
        }



        //delete the country
        $deleteCountry = DB::table('cities')
            ->where('citiesId', $request->citiesId)
            ->delete();
        if ($deleteCountry) {
            return response(["message" => "city deleted successfully"], 200);
        } else {
            return response(["message" => "Something went wrong"], 500);
        }
    }

    public function allCityList(Request $request)
    {
        $states = DB::table('cities')
            ->join('continents', 'cities.continentId', '=', 'continents.continentId')
            ->join('countries', 'cities.countryId', '=', 'countries.countryId')
            ->join('states', 'cities.stateId', '=', 'states.stateId')
            ->select('cities.*', 'countries.countryName', 'continents.continentName', 'states.stateName')
            ->paginate($request->perPage == null ? 10 : $request->perPage);
        if ($states->isEmpty()) {
            $stateArray = [];
        }
        $myObj = new \stdClass();
        foreach ($states as  $value) {
            $myObj->citiesId = $value->citiesId;
            $myObj->citiesName = $value->citiesName;
            $myObj->stateName = $value->stateName;
            $myObj->countryName = $value->countryName;
            $myObj->continentName = $value->continentName;
            $stateArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'data' => $stateArray,
            'total' => $states->total(),
            'currentPage' => $states->currentPage(),
            'perPage' => $states->perPage(),
            'nextPageUrl' => $states->nextPageUrl(),
            'previousPageUrl' => $states->previousPageUrl(),
            'lastPage' => $states->lastPage()
        ], 200);
    }

    //add city
    public function addCity(Request $request)
    {

        $validateData = Validator::make($request->all(), [
            'countryId' => 'required',
            'continentId' => 'required',
            'stateId' => 'required',
            'citiesName' => 'required'
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 400);
        }



        //checks the token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }


        $check = DB::table('states')
            ->where('continentId', $request->continentId)
            ->where('stateId', $request->stateId)
            ->where('countryId', $request->countryId)
            ->first();
        if (!$check) {
            return response()->json(['message' => 'Contienent-country-city not matched'], 404);
        }


        $cityList = DB::table('cities')->insert([
            'continentId' => $request->continentId,
            'countryId' => $request->countryId,
            'stateId' => $request->stateId,
            'citiesName' => $request->citiesName
        ]);
        if ($cityList) {
            return response()->json(['message' => 'City Added Successfully'], 200);
        } else {
            return response()->json(['message' => 'Something went wrong'], 500);
        }
    }

    //city list
    public function cityList(Request $request)
    {
        $cityLists = DB::table('cities');

        if (!empty($request->stateId)) {
            $cityLists->where('stateId', $request->stateId);
        } elseif (!empty($request->countryId)) {
            $cityLists->where('countryId', $request->countryId);
        }

        $cityList = $cityLists->get();

        if ($cityList->isEmpty()) {
            $cityArray = [];
        }
        $myObj = new \stdClass();
        foreach ($cityList as  $value) {
            $myObj->citiesId  = $value->citiesId;
            $myObj->stateId = $value->stateId;
            $myObj->citiesName = $value->citiesName;

            $cityArray[] = $myObj;
            $myObj = new \stdClass();
        }
        return response()->json([
            'data' => $cityArray
        ], 200);
    }

    //salesTargetCt
    public function salesTargetCt(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $target = [];

        foreach ($request->targetArrayCt as $key => $value) {
            $target[] = [
                'userId' => $value['userId'],
                'monthId' => $value['monthId'],
                'target' => $value['target'],
                'yearId' => $value['yearId'],
                'tourType' => $value['tourType'],
                'quarterId' => $value['quarterId']
            ];
        }
        if (empty($target)) {
            return ['message' => 'Target array is empty'];
        }

        $targetDetails = DB::table('salesTraget')->insert($target);
        if (!$targetDetails) {
            return ['message' => 'Sales target for customize tour not added', 'status' => false];
        }
        return response()->json(['message' => 'Traget added successfully for customize tour'], 200);
    }

    //tour code unique api
    public function tourCode(Request $request)
    {
        $validateData = Validator::make($request->all(), [
            'tourCode' => 'required'
        ]);
        if ($validateData->fails()) {
            return response()->json(['message' => $validateData->errors()->all()], 422);
        }
        $tourCode = DB::table('grouptours')->where('tourCode', $request->tourCode)->first();
        if ($tourCode) {
            return response()->json(['message' => 'This tour code is already taken'], 409);
        }
    }

    //admin dashboard
    public function adminDashboard(Request $request)
    {
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [1]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }

        //count of group-tours
        $groupToursCount = DB::table('grouptours')->count();
        // dd($groupToursCount);
        $salesCount = DB::table('users')->where('roleId', 2)->count();
        return response()->json([
            'groupToursCount' => $groupToursCount,
            'salesCount' => $salesCount
        ], 200);
    }
}
