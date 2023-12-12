<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Sales\CustomTourController;
use App\Http\Controllers\Sales\SalesController;
use App\Http\Controllers\Sales\GroupTourController;
use App\Http\Controllers\Operations\AuthController;
use App\Http\Controllers\Operations\GroupController;
use App\Http\Controllers\Accounts\AccountController;
use App\Http\Controllers\Operations\CustomizeTourController;
use App\Http\Controllers\Accounts\AccCTController;
use App\Http\Controllers\Sales\SalesDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


//admin-login
Route::post('admin-login', [AdminController::class, 'adminLogin']);

//admin dashboard
Route::get('/admin-dashboard', [AdminController::class, 'adminDashboard']);

//tourCode
Route::post('/tour-code', [AdminController::class, 'tourCode']);

//add tour type
Route::post('add-tour-type', [AdminController::class, 'addTourType']);
//edit tour type
Route::post('/edit-tour-type', [AdminController::class, 'editTourType']);
//delete tour type
Route::get('/delete-tour-type', [Admincontroller::class, 'deleteTourType']);
//list tour type
Route::get('/tour-type-list', [AdminController::class, 'tourTypeList']);

//listing destination
Route::get('/destination-list', [AdminController::class, 'destinationList']);

//listing departure type
Route::get('/departure-type-list', [AdminController::class, 'departureTypeList']);

//listing vehicle
Route::get('/vehicle-listing', [AdminController::class, 'vehicleListing']);

//listing kitchen
Route::get('/kitchen-list', [AdminController::class, 'kitchenList']);

//listing-meal plan
Route::get('/meal-plan-list', [AdminController::class, 'mealPlanList']);

//listing meal type
Route::get('/meal-type-list', [AdminController::class, 'mealTypeList']);

//enquiry reference list
Route::get('/enquiry-reference-list', [AdminController::class, 'enquiryReferenceList']);

//priority-listing
Route::get('/priority-list', [AdminController::class, 'priorityList']);

//relation with family head
Route::get('/relation-list', [AdminController::class, 'relationList']);
//room sharing list
Route::get('/room-share-list', [AdminController::class, 'roomSharingList']);
//PAYment mode
Route::get('/payment-mode-list', [AdminController::class, 'paymentModeList']);
//online type listing
Route::get('/online-type-list', [AdminController::class, 'onlineTypeList']);

//cities dropdown
Route::get('/dropdown-city', [AdminController::class, 'dropdownCity']);

//add group-tour
Route::post('/add-group-tour', [AdminController::class, 'addGroupTour']);

//specific group-tour details
Route::get('/details-group-tour', [AdminController::class, 'detailsGroupTour']);

//listing group-tour
Route::get('/group-tour-list', [AdminController::class, 'groupTourList']);

//edit group-tour listing
Route::post('/update-group-tour-list', [AdminController::class, 'editGroupTourListing']);

//delete group-tour listing
Route::get('/delete-group-tour-list', [AdminController::class, 'deleteGroupTourList']);

//sales listing
Route::get('/sales-listing', [AdminController::class, 'salesListing']);

//dropdown months
Route::get('/dropdown-months', [AdminController::class, 'dropdownMonths']);

//dropdown years
Route::get('/dropdown-years', [AdminController::class, 'dropdownYears']);

//add sales target group tour
Route::post('/sales-target-gt', [AdminController::class, 'salesTargetGt']);

//add sales target custom tour
Route::post('/sales-target-ct', [AdminController::class, 'salesTargetCt']);

// salesTargetList
Route::get('/sales-target-list', [AdminController::class, 'salesTargetList']);

//ctTourLists
Route::get('/ct-tour-lists', [AdminController::class, 'ctTourLists']);

//dropdownDepartState
Route::get('/dd-country-state', [AdminController::class, 'ddCountryState']);

//addSales
Route::post('/add-sales', [AdminController::class, 'addSales']);

//dropdownContinents
Route::get('/dropdown-continents', [AdminController::class, 'dropdownContinents']);

//add country
Route::post('/add-country', [AdminController::class, 'addCountry']);

//country dropdown
Route::get('/country', [AdminController::class, 'country']);

//add state
Route::post('/add-state', [AdminController::class, 'addState']);

//stateList
Route::post('/state-list', [AdminController::class, 'stateList']);

//addCity
Route::post('/add-city', [AdminController::class, 'addCity']);

//cityList
Route::get('/city-list', [AdminController::class, 'cityList']);

//continent wisecontry list
Route::get('/continent-country-list', [AdminController::class, 'continentCountryList']);

Route::get('/continent-country-state-list', [AdminController::class, 'continentCountryStateList']);

//all state list
Route::get('/all-state-list', [AdminController::class, 'allStateList']);
Route::get('/delete-state', [AdminController::class, 'deleteState']);

//all city list
Route::get('/all-city-list', [AdminController::class, 'allCityList']);
Route::get('/delete-city', [AdminController::class, 'deleteCity']);

//all country list
Route::get('/all-country-list', [AdminController::class, 'allCountryList']);
Route::get('/delete-country', [AdminController::class, 'deleteCountry']);
Route::get('/edit-country', [AdminController::class, 'editCountry']);




//********************sales*************************************/
//sales-login
Route::post('/sales-login', [SalesController::class, 'salesLogin']);

//guest info grouptour
Route::get('/group-guest-info', [SalesController::class, 'groupGuestInfo']);

//guest info custom info
Route::get('/custom-guest-info', [SalesController::class, 'customGuestInfo']);

//dropdown tour name
Route::get('/group-tour-dropdown', [GroupTourController::class, 'groupTourDropdown']);

//enquiry group-tour
Route::post('/enquiry-group-tour', [GroupTourController::class, 'enquiryGroupTour']);

//listgroupTour (enquiry follow up)
Route::get('/list-group-tour', [GroupTourController::class, 'listGroupTour']);
//upcoming-list-group-tour
Route::get('/upcoming-list-group-tour', [GroupTourController::class, 'upcomingListGroupTour']);
//
//nextFollowUp update
Route::post('/update-group-follow-up', [GroupTourController::class, 'updateGroupFollowUp']);

//view-group-tour (view tours)
Route::get('/view-group-tour', [GroupTourController::class, 'viewGroupTour']);

//group tour details
Route::get('/view-details-group-tour', [GroupTourController::class, 'viewDetailsGroupTour']);

//group detail senquiry
Route::get('/enqGroup-details', [GroupTourController::class, 'enqGroupDetails']);
//enquiry group tour details
Route::post('/enquiry-group-details', [GroupTourController::class, 'enquiryGroupDetails']);

//discount group details
Route::post('/discount-details', [GroupTourController::class, 'discountDetails']);

//payment details group tour
Route::post('/payment-details', [GroupTourController::class, 'paymentDetails']);

//confirm group tour list
Route::get('/confirm-group-tour-list', [GroupTourController::class, 'confirmGroupTourList']);

//view billing confirm group tour
Route::get('/view-bill-group-tour', [GroupTourController::class, 'viewBillGroupTour']);

//dropdowbTravelMode
Route::get('dropdowbTravelMode', [GroupTourController::class, 'dropdowbTravelMode']);

//receive bill group tour
Route::post('/receivebill-group-tour', [GroupTourController::class, 'receiveBillGroupTour']);

//cancel enquiry for ongoing enquiry
Route::post('/cancel-enquiry-group-tour', [GroupTourController::class, 'cancelEnquiryGroupTour']);

//lost enquiries for sales
Route::get('/lost-enquiry-group-tour', [GroupTourController::class, 'lostEnquiryGroupTour']);

//booking records
Route::get('/booking-records', [GroupTourController::class, 'bookingRecords']);

//dropdownRoomPrice
Route::get('/dropdownRoomPrice', [GroupTourController::class, 'dropdownRoomPrice']);

//pdf store
Route::post('/image-upload', [GroupTourController::class, 'imageUpload']);

//dropdownGuestRefId
Route::get('/dropdown-guest-refId', [GroupTourController::class, 'dropdownGuestRefId']);

//guest information
Route::get('/guest-information', [SalesController::class, 'guestInformation']);

//guestDetails
Route::get('/guest-details', [SalesController::class, 'guestDetails']);

//dropdownGuestType
Route::get('/dropdown-guest-type', [SalesController::class, 'dropdownGuestType']);

//dropdown guest names
Route::get('/guest-names-dropdown', [SalesController::class, 'guestNamesDropdown']);

//guestsInfo
Route::get('/guests-info', [SalesController::class, 'guestsInfo']);

//dropdownCardType
Route::get('/dropdown-card-type', [SalesController::class, 'dropdownCardType']);

//loyalty card purchase
Route::post('/card-purchase', [SalesController::class, 'cardPurchase']);

//loyalGuestsLists
Route::get('/loyal-guests-lists', [SalesController::class, 'loyalGuestsLists']);

//loyalguest view
Route::get('/loyal-guests-view', [SalesController::class, 'loyalGuestView']);

//***************************custom tour***********/
Route::get('/dropdown-hotel-cat', [CustomTourController::class, 'ddHotelCat']);

//enquiry custom tour
Route::post('/enquiry-custom-tour', [CustomTourController::class, 'enquiryCustomTour']);


//update enquiry custom tour details
Route::post('/update-enquiry-custom-details', [CustomTourController::class, 'updateEnquiryCustomTour']);

//enquiry details custom tour
Route::post('/enquiry-custom-tour-details', [CustomTourController::class, 'enquiryCustomTourDetails']);

//dropdown roomshare list for custome tour
Route::get('/dropdown-rooms-packages', [CustomTourController::class, 'dropdownRoomPackages']);

//guest details custom tour
Route::post('/custom-guest-details', [CustomTourController::class, 'customGuestDetails']);

//family head listing
Route::get('familyhead-list', [CustomTourController::class, 'familyHeadList']);

//package pdf listing
Route::get('/package-list', [CustomTourController::class, 'packageList']);

//enquiry-FollowUp CustomTourList
Route::get('/enquiry-follow-custom', [CustomTourController::class, 'enquiryFollowCustomTourList']);
//upcoming enquiry follow up
Route::get('/upcoming-enquiry-follow-CT', [CustomTourController::class, 'upcomingenquiryFollowCT']);
//update next follow up date custom tour
Route::post('/update-next-followup', [CustomTourController::class, 'updateNextFollowUp']);

//enquiry custom details
Route::get('/enquiry-ct', [CustomTourController::class, 'enquiryCt']);

//cancel ongoing custom enquiry
Route::post('/cancel-custom-enquiry', [CustomTourController::class, 'cancelCustomEnquiry']);

//listing lost enquiry
Route::get('/lost-enquiry-custom', [CustomTourController::class, 'lostEnquiryCustomTour']);

//confirm custom tour listing
Route::get('/confirm-custom-list', [CustomTourController::class, 'confirmCustomList']);

//view bill custom tour
Route::get('/view-bill-ct', [CustomTourController::class, 'viewBillCT']);
//receive bill custom tour
Route::post('/receive-bill-ct', [CustomTourController::class, 'receiveBillCT']);

//view custom tour
Route::get('/view-custom-tour', [CustomTourController::class, 'viewCustomTour']);
//finalize package
Route::post('/final-package', [CustomTourController::class, 'finalPackage']);

//dropdownCountry
Route::post('/country-state', [CustomTourController::class, 'countryState']);

//*****************Sales Dashboard********************/
Route::get('/top-sales', [SalesDashboardController::class, 'topSales']);

//salesGraphGt
Route::get('/sales-graph', [SalesDashboardController::class, 'salesGraph']);

//********************operation*******************************/

//operation login
Route::post('/operation-login', [AuthController::class, 'operationLogin']);

//today's follow up group tour operations
Route::get('/today-follow-list', [GroupController::class, 'todayFollowList']);

//upcomin follow list
Route::get('/upcoming-list-gt', [GroupController::class, 'upcomingListGt']);


//confirmed group tour list
Route::get('/confirmed-group-tour-list', [GroupController::class, 'confirmedGroupTourList']);

//guest details for confirmed group tour
Route::get('/guest-confirm-group-tour', [GroupController::class, 'guestConfirmGroupTour']);


/**********************customize tours api***************************/
//pdf upload
Route::post('/package-upload', [CustomizeTourController::class, 'packageUpload']);

Route::post('/package-custom-tour', [CustomizeTourController::class, 'packageCustomTour']);


//confirmed custom tours
Route::get('/confirm-custom-tours', [CustomizeTourController::class, 'confirmCustomTours']);

//enquiry-follow-custom-operation
Route::get('/enquiry-follow-custom-operation', [CustomizeTourController::class, 'enquiryCustomOperation']);

//upcoming-enquiry-follow-CT-operation
Route::get('/upcoming-enquiry-follow-CT-operation', [CustomizeTourController::class, 'upcomingfollowCtOperation']);






//***************Accounts*********************//
Route::post('/account-login', [AccountController::class, 'accountLogin']);

//payment status
// Route::post('/payment-group-tour', [AccountController::class, 'paymentGroupTour']);

/******************* group Tour ***********************/
Route::get('/confirmpay-list', [AccountController::class, 'confirmPayList']);

//pending pay list
Route::get('/paypending-list', [AccountController::class, 'payPendingList']);

//update pay status to 1
Route::post('/update-pay-status', [AccountController::class, 'updatePayStatus']);

//viewNewPay
Route::get('/view-new-pay', [AccountController::class, 'viewNewPay']);

//viewReceipt
Route::get('/view-receipt', [AccountController::class, 'viewReceipt']);
//viewNewPayDetails
Route::get('/viewNew-pay-details', [AccountController::class, 'viewNewPayDetails']);


/**************************Custom Tour******************************/

//confirm payment list
Route::get('/confirm-pay-list-ct', [AccCTController::class, 'confirmPayListCT']);

//pending payment list
Route::get('/pending-pay-list-ct', [AccCTController::class, 'pendingPayListCT']);

//update pending payment to confirm
Route::post('/update-pay-status-ct', [AccCTController::class, 'updatePayStatusCT']);

//viewNewPayCT
Route::get('/viewNewPayCT', [AccCTController::class, 'viewNewPayCT']);

//viewReceiptCT
Route::get('/viewReceiptCT', [AccCTController::class, 'viewReceiptCT']);

//pending card payments
Route::get('/pending-card-purchase', [AccountController::class, 'pendingCardPurchase']);

//updated status of card pays
Route::post('/update-card-pays', [AccountController::class, 'updateCardPays']);


/****************************Dashboard**********************************/
Route::get('account-dashboard', [AccountController::class, 'accountDashboard']);

//////////////////////////////
Route::get('ct', [CustomTourController::class, 'ct']);



/*******************************Loyaltypoints Calculations*************************************/

//dropdown guest details group tours
Route::get('list-guests-names', [GroupTourController::class, 'listGuestsNames']);

//guestDetail group tour
Route::post('/guest-detail', [GroupTourController::class, 'guestDetail']);

//dropdown guest details custom tours
Route::get('/guest-names-ct', [GroupTourController::class, 'guestNamesCt']);

//guest details custom tours
Route::post('/guest-detail-ct', [GroupTourController::class, 'guestDetailCt']);
