<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\CommonController;
use Illuminate\Support\Facades\DB;

class SalesDashboardController extends Controller
{
    //top 5 sales grouptour
    public function topSales(Request $request){
        //checks token 
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        $topSalesGt = DB::table('enquirygrouptours')
        ->select(
            'enquirygrouptours.createdBy',
            'users.userName',
            DB::raw('COUNT(*) as enquiryCount'),
            DB::raw('SUM(CASE WHEN grouptours.destinationId = 1 THEN 1 ELSE 0 END) AS domesticCount'),
            DB::raw('SUM(CASE WHEN grouptours.destinationId = 2 THEN 1 ELSE 0 END) AS internationalCount')
        )
            ->join('grouptours', 'enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
            ->join('users', 'enquirygrouptours.createdBy', '=', 'users.userId')
            ->whereNotNull('enquirygrouptours.createdBy')
            ->where('enquirygrouptours.createdBy', '!=', $tokenData->userId)
            ->where('enquirygrouptours.enquiryProcess', 2)
            ->groupBy('enquirygrouptours.createdBy', 'users.userName')
            ->orderBy('enquiryCount', 'desc')
            ->take(5)
            ->get();

        $topSalesCt = DB::table('enquiryCustomTours')
        ->select(
            'enquiryCustomTours.createdBy',
            'users.userName',
            DB::raw('COUNT(*) as enquiryCount'),
            DB::raw('SUM(CASE WHEN enquiryCustomTours.destinationId = 1 THEN 1 ELSE 0 END) AS domesticCount'),
            DB::raw('SUM(CASE WHEN enquiryCustomTours.destinationId = 2 THEN 1 ELSE 0 END) AS internationalCount')
        )
            ->join('users', 'enquiryCustomTours.createdBy', '=', 'users.userId')
            ->whereNotNull('enquiryCustomTours.createdBy')
            ->where('enquiryCustomTours.createdBy', '!=', $tokenData->userId)
            ->where('enquiryCustomTours.enquiryProcess', 2)
            ->groupBy('enquiryCustomTours.createdBy', 'users.userName')
            ->orderBy('enquiryCount', 'desc')
            ->take(5)
            ->get();
            // dd($topSalesCt);
    
        return response()->json([
            'topSalesGt' => $topSalesGt,
            'topSalesCt' => $topSalesCt
        ]);
    }

    //graph monthly target
    public function salesGraph(Request $request){
        //checks token
        $tokenData = CommonController::checkToken($request->header('token'), [2]);
        if (!$tokenData) {
            return response()->json(['message' => 'Invalid Token'], 408);
        }
        //monthly target
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $currentMonth = now()->month;

        if ($currentMonth >= 1 && $currentMonth <= 3) {
            $currentQuarter = 4;
        } elseif ($currentMonth >= 4 && $currentMonth <= 6) {
            $currentQuarter = 1;
        } elseif ($currentMonth >= 7 && $currentMonth <= 9) {
            $currentQuarter = 2;
        } else {
            $currentQuarter = 3;
        }
        // dd($currentQuarter);
        // dd( $currentYear);
        $achievedTargetMonthly = DB::table('enquirygrouptours')
            ->where('enquiryprocess', 2)
            ->where('createdBy', $tokenData->userId)
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->count();
        // dd($achievedTargetMonthly);
        //target set monthly
        $targetSet = DB::table('salesTraget')
            ->join('dropdownMonths', 'salesTraget.monthId', '=', 'dropdownMonths.monthId')
            ->where('salesTraget.userId', $tokenData->userId)
            ->where('salesTraget.tourType', 1)
            ->where('dropdownMonths.monthId',$currentMonth )
            ->value('target');
    
        //remaining target monthly
        $remainingTarget = $targetSet -  $achievedTargetMonthly;

        //year target
        $targetSetYear = DB::table('salesTraget')
        ->join('dropdownYears', 'salesTraget.yearId', '=', 'dropdownYears.yearId')
        ->where('salesTraget.userId', $tokenData->userId)
        ->where('salesTraget.tourType', 1)
        ->where('dropdownYears.year',$currentYear)
        ->sum('target');
        // dd( $targetSet);
        $achievedTargetYearly = DB::table('enquirygrouptours')
            ->where('enquiryprocess', 2)
            ->where('createdBy', $tokenData->userId)
            ->whereYear('created_at', $currentYear)
            ->count();
        // dd($achievedTargetYearly);
        $remainingTargetYear = $targetSetYear-$achievedTargetYearly;
        // dd($remainingTargetYear);
        $quarterTarget = DB::table('salesTraget')
            ->join('dropdownQuarter', 'salesTraget.quarterId', '=', 'dropdownQuarter.quarterId')
            ->where('salesTraget.userId', $tokenData->userId)
            ->where('salesTraget.tourType', 1)
            ->where('dropdownQuarter.quarter', $currentQuarter)
            ->sum('target');
        // dd( $quarterTarget);
        $achievedTargetQuarter = DB::table('enquirygrouptours')
            ->where('enquiryProcess', 2)
            ->where('createdBy', $tokenData->userId)
            ->where(function ($query) use ($currentQuarter) {
                if ($currentQuarter == 1) {
                    $query->whereMonth('created_at', '>=', 4)->whereMonth('created_at', '<=', 6);
                } elseif ($currentQuarter == 2) {
                    $query->whereMonth('created_at', '>=', 7)->whereMonth('created_at', '<=', 9);
                } elseif ($currentQuarter == 3) {
                    $query->whereMonth('created_at', '>=', 10)->whereMonth('created_at', '<=', 12);
                } else { // For quarter 4
                    $query->whereMonth('created_at', '>=', 1)->whereMonth('created_at', '<=', 3);
                }
            })
            ->count();
        // dd($achievedTargetQuarter);
        $remainingTargetQuarter = $quarterTarget - $achievedTargetQuarter;

        $totalEnquiryMonth = DB::table('enquirygrouptours')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
            ->where('createdBy', $tokenData->userId)
            // ->where('enquiryProcess', 1)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->get();

        $confirmedEnquiryMonth = DB::table('enquirygrouptours')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
            ->where('createdBy', $tokenData->userId)
            ->where('enquiryProcess', 2)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->get();
        // dd($confirmedEnquiryMonth);    
        $lostEnquiryMonth = DB::table('enquirygrouptours')
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
            ->where('createdBy', $tokenData->userId)
            ->where('enquiryProcess', 3)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->get();
        // dd( $lostEnquiryMonth);
        
        //bithday table
        $currentDate = now()->format('Y-m-d'); 

        $guestsWithCurrentDOBGt = DB::table('grouptourguestdetails')
            ->select('familyHeadName','dob', 'contact')
            ->where('createdBy', $tokenData->userId)
            ->whereDate('dob', $currentDate)
            ->get();
        // dd($guestsWithCurrentDOBGt);

        $guestsWithCurrentDOBCt = DB::table('customTourGuestDetails')
            ->select('familyHeadName','dob', 'contact')
            ->where('createdBy', $tokenData->userId)
            ->whereDate('dob', $currentDate)
            ->get();
        // dd($guestsWithCurrentDOBCt);
        $combinedBirthday = array_merge($guestsWithCurrentDOBGt->toArray(), $guestsWithCurrentDOBCt->toArray());
        // dd($combinedResults);
        //top 5 sales partner 
        $topSalesGt = DB::table('enquirygrouptours')
        ->select(
            'enquirygrouptours.createdBy',
            'users.userName',
            DB::raw('COUNT(*) as enquiryCount'),
            DB::raw('SUM(CASE WHEN grouptours.destinationId = 1 THEN 1 ELSE 0 END) AS domesticCount'),
            DB::raw('SUM(CASE WHEN grouptours.destinationId = 2 THEN 1 ELSE 0 END) AS internationalCount')
        )
            ->join('grouptours', 'enquirygrouptours.groupTourId', '=', 'grouptours.groupTourId')
            ->join('users', 'enquirygrouptours.createdBy', '=', 'users.userId')
            ->whereNotNull('enquirygrouptours.createdBy')
            ->where('enquirygrouptours.createdBy', '!=', $tokenData->userId)
            ->where('enquirygrouptours.enquiryProcess', 2)
            ->groupBy('enquirygrouptours.createdBy', 'users.userName')
            ->orderBy('enquiryCount', 'desc')
            ->take(5)
            ->get();
        // dd($topSalesGt);
        $topSalesCt = DB::table('enquiryCustomTours')
        ->select(
            'enquiryCustomTours.createdBy',
            'users.userName',
            DB::raw('COUNT(*) as enquiryCount'),
            DB::raw('SUM(CASE WHEN enquiryCustomTours.destinationId = 1 THEN 1 ELSE 0 END) AS domesticCount'),
            DB::raw('SUM(CASE WHEN enquiryCustomTours.destinationId = 2 THEN 1 ELSE 0 END) AS internationalCount')
        )
            ->join('users', 'enquiryCustomTours.createdBy', '=', 'users.userId')
            ->whereNotNull('enquiryCustomTours.createdBy')
            ->where('enquiryCustomTours.createdBy', '!=', $tokenData->userId)
            ->where('enquiryCustomTours.enquiryProcess', 2)
            ->groupBy('enquiryCustomTours.createdBy', 'users.userName')
            ->orderBy('enquiryCount', 'desc')
            ->take(5)
            ->get();

    //customize tours
    $targetSetMonthlyCt = DB::table('salesTraget')
        ->join('dropdownMonths', 'salesTraget.monthId', '=', 'dropdownMonths.monthId')
        ->where('salesTraget.userId', $tokenData->userId)
        ->where('salesTraget.tourType', 2)
        ->where('dropdownMonths.monthId',$currentMonth )
        ->value('target');
    // dd($targetSetMonthlyCt);
    $achievedTargetMonthlyCt = DB::table('customTourPaymentDetails')
            ->where('createdBy', $tokenData->userId)
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->where('status', 1)
            ->sum('balance');
    // dd($achievedTargetMonthlyCt);
    $remainingTargetMonthlyCt = $targetSetMonthlyCt-$achievedTargetMonthlyCt;
  
    $targetSetYearCt = DB::table('salesTraget')
        ->join('dropdownYears', 'salesTraget.yearId', '=', 'dropdownYears.yearId')
        ->where('salesTraget.userId', $tokenData->userId)
        ->where('salesTraget.tourType', 2)
        ->where('dropdownYears.year',$currentYear)
        ->sum('target');
    // dd($targetSetYearCt);
    $achievedTargetYearly = DB::table('customTourPaymentDetails')
        ->where('createdBy', $tokenData->userId)
        ->whereYear('created_at', $currentYear)
        ->where('status', 1)
        ->sum('balance');
    // dd($achievedTargetYearly);
    $remainingTargetYearlyCt = $targetSetYearCt - $achievedTargetYearly;
    // dd($remainingTargetYearlyCt);

    $targetSetQuarterlyCt = DB::table('salesTraget')
        ->join('dropdownQuarter', 'salesTraget.quarterId', '=', 'dropdownQuarter.quarterId')
        ->where('salesTraget.userId', $tokenData->userId)
        ->where('salesTraget.tourType', 2)
        ->where('dropdownQuarter.quarter', $currentQuarter)
        ->sum('target');
    // dd($targetSetQuarterlyCt);
    $achievedTargetQuarterCt = DB::table('customTourPaymentDetails')
        ->where('createdBy', $tokenData->userId)
        ->where(function ($query) use ($currentQuarter) {
            if ($currentQuarter == 1) {
                $query->whereMonth('created_at', '>=', 4)->whereMonth('created_at', '<=', 6);
            } elseif ($currentQuarter == 2) {
                $query->whereMonth('created_at', '>=', 7)->whereMonth('created_at', '<=', 9);
            } elseif ($currentQuarter == 3) {
                $query->whereMonth('created_at', '>=', 10)->whereMonth('created_at', '<=', 12);
            } else {
                $query->whereMonth('created_at', '>=', 1)->whereMonth('created_at', '<=', 3);
            }
        })
        ->where('status', 1)
        ->sum('balance');
    // dd($achievedTargetQuarterCt);
    $remainingTargetQuarterCt =  $targetSetQuarterlyCt - $achievedTargetQuarterCt ;
    
    $totalEnquiryMonthCt = DB::table('enquiryCustomTours')
    ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
    ->where('createdBy', $tokenData->userId)
    ->groupBy(DB::raw('MONTH(created_at)'))
    ->get();
    // dd($totalEnquiryMonthCt);
    
    $confirmedEnquiryMonthCt = DB::table('enquiryCustomTours')
    ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
    ->where('createdBy', $tokenData->userId)
    ->where('enquiryProcess', 2)
    ->groupBy(DB::raw('MONTH(created_at)'))
    ->get();

    $lostEnquiryMonthCt = DB::table('enquiryCustomTours')
    ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as count'))
    ->where('createdBy', $tokenData->userId)
    ->where('enquiryProcess', 3)
    ->groupBy(DB::raw('MONTH(created_at)'))
    ->get();
     return response()->json([
            'targetSet' => $targetSet,
            'achievedTargetMonthly' => $achievedTargetMonthly,
            'remainingTarget' =>$remainingTarget,
            'targetSetYear' => $targetSetYear,
            'achievedTargetYearly' => $achievedTargetYearly,
            'remainingTargetYear' => $remainingTargetYear,
            'quarterTarget' => $quarterTarget,
            'achievedTargetQuarter' => $achievedTargetQuarter,
            'remainingTargetQuarter' => $remainingTargetQuarter,
            'totalEnquiryMonth' => $totalEnquiryMonth,
            'confirmedEnquiryMonth' => $confirmedEnquiryMonth,
            'lostEnquiryMonth' => $lostEnquiryMonth,
            'topSalesGt' => $topSalesGt,
            'topSalesCt' => $topSalesCt,
            'combinedBirthday' => $combinedBirthday,
            'targetSetMonthlyCt' => $targetSetMonthlyCt,
            'achievedTargetMonthlyCt' => $achievedTargetMonthlyCt,
            'remainingTargetMonthlyCt' => $remainingTargetMonthlyCt,
            'targetSetYearCt' => $targetSetYearCt,
            'achievedTargetYearly' => $achievedTargetYearly,
            'remainingTargetYearlyCt' => $remainingTargetYearlyCt,
            'targetSetQuarterlyCt' =>$targetSetQuarterlyCt,
            'achievedTargetQuarterCt' => $achievedTargetQuarterCt,
            'remainingTargetQuarterCt' => $remainingTargetQuarterCt,
            'totalEnquiryMonthCt' => $totalEnquiryMonthCt,
            'confirmedEnquiryMonthCt' => $confirmedEnquiryMonthCt,
            'lostEnquiryMonthCt' => $lostEnquiryMonthCt

        ], 200);
    
    }


}
