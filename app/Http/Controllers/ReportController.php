<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Models\Admin\GameType;
use App\Models\Admin\Product;
use App\Models\FinicalReport;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;


class ReportController extends Controller
{



    public function index(Request $request)
    {
        $query = DB::table('reports')
            ->join('users', 'reports.member_name', '=', 'users.user_name')
            ->select(
                'users.name as member_name',
                'users.user_name as user_name',
                DB::raw('COUNT(DISTINCT reports.id) as qty'),
                DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
                DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
                DB::raw('SUM(reports.payout_amount) as total_payout_amount'),
                DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
                DB::raw('SUM(reports.jack_pot_amount) as total_jack_pot_amount'),
                DB::raw('SUM(reports.jp_bet) as total_jp_bet'),
                DB::raw('(SUM(reports.payout_amount) - SUM(reports.valid_bet_amount)) as win_or_lose'),
                DB::raw('COUNT(*) as stake_count')
            );
        if (isset($request->start_date) && isset($request->end_date)) {
            $query->whereBetween('reports.created_at', [$request->start_date.' 00:00:00', $request->end_date.' 23:59:59']);
        } elseif (isset($request->member_name)) {
            $query->where('reports.member_name', $request->member_name);
        }
        if (! Auth::user()->hasRole('Admin')) {
            $query->where('reports.agent_id', Auth::id());
        }
        $agentReports = $query->groupBy('reports.member_name', 'users.name', 'users.user_name')->get();

        return view('report.show', compact('agentReports'));
    }

    // amk
    public function detail(Request $request, $userName)
    {
        if ($request->ajax()) {
            $query = DB::table('reports')
                ->join('users', 'reports.member_name', '=', 'users.user_name')
                ->join('products', 'products.code', '=', 'reports.product_code')
                ->where('reports.member_name', $userName)
                ->orderBy('reports.id', 'desc')
                ->select(
                    'reports.*',
                    'users.name as name',
                    'products.name as product_name',
                    DB::raw('(reports.payout_amount - reports.valid_bet_amount) as win_or_lose')
                );
            if (! Auth::user()->hasRole('Admin')) {
                return $query->where('reports.agent_id', Auth::id());
            }
            $report = $query->get();

            return DataTables::of($report)
                ->addIndexColumn()
                ->make(true);
        }
        $products = Product::all();

        return view('report.detail', compact('products', 'userName'));
    }
}

    // public function index(Request $request)
    // {
    //     $reports = $this->makeJoinTable()->select(
    //         'products.name as product_name',
    //         'products.code',
    //         DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //         DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //         DB::raw('SUM(reports.payout_amount) as total_payout_amount'))
    //         ->groupBy('product_name', 'products.code')
    //         ->when(isset($request->fromDate) && isset($request->toDate), function ($query) use ($request) {
    //             $query->whereBetween('reports.settlement_date', [$request->fromDate, $request->toDate]);
    //         })
    //         ->get();

    //     return view('report.index', compact('reports'));
    // }

    // public function index(Request $request)
    // {
    //     $reports = $this->makeJoinTable()->select(
    //         'users.user_name',
    //         'users.id as user_id',
    //         'reports.agent_commission',
    //         DB::raw('SUM(reports.commission_amount) as total_commission_amount'),
    //         DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //         DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //         DB::raw('SUM(reports.payout_amount) as total_payout_amount'))
    //         ->groupBy('users.user_name', 'users.id', 'reports.agent_commission')
    //         ->when(isset($request->player_name), function ($query) use ($request) {
    //             $query->whereBetween('reports.member_name', $request->player_name);
    //         })
    //         ->when(isset($request->fromDate) && isset($request->toDate), function ($query) use ($request) {
    //             $query->whereBetween('reports.settlement_date', [$request->fromDate, $request->toDate]);
    //         })
    //         ->get();

    //     return view('report.show', compact('reports'));
    // }

    // // amk
    // public function detail(Request $request, int $userId)
    // {
    //     $report = $this->makeJoinTable()
    //         ->select(
    //             'products.name as product_name',
    //             'users.user_name',
    //             'users.id as user_id',
    //             'reports.wager_id',
    //             'reports.valid_bet_amount',
    //             'reports.bet_amount',
    //             'reports.payout_amount',
    //             'reports.settlement_date',
    //             'game_lists.code as game_code',
    //             'game_lists.name as game_list_name'
    //         )
    //         ->where('users.id', $userId)
    //         ->when($request->has('fromDate') && $request->has('toDate'), function ($query) use ($request) {
    //             $query->whereBetween('reports.settlement_date', [$request->fromDate, $request->toDate]);
    //         })
    //         ->get();

    //     $player = User::find($userId);

    //     return view('report.detail', compact('report', 'player'));
    // }

    // sophia
    // public function detail(Request $request, int $userId)
    // {

    //     $report = $this->makeJoinTable()
    //         ->select(
    //             'products.name as product_name',
    //             'users.user_name',
    //             'users.id as user_id',
    //             'reports.valid_bet_amount',
    //             'reports.bet_amount',
    //             'reports.payout_amount',
    //             'reports.settlement_date'
    //         )
    //         ->where('users.id', $request->user_id)
    //         ->when(isset($request->product_code), function ($query) use ($request) {
    //             $query->where('reports.product_code', $request->product_code);
    //         })
    //         ->when(isset($request->fromDate) && isset($request->toDate), function ($query) use ($request) {
    //             $query->whereBetween('reports.settlement_date', [$request->fromDate, $request->toDate]);
    //         })
    //         ->get();

    //     $player = User::find($userId);

    //     return view('report.detail', compact('report', 'player'));
    // }

    // public function view($user_name)
    // {
    //     $reports = $this->makeJoinTable()->select(
    //         'users.user_name',
    //         'users.id as user_id',
    //         'products.name as product_name',
    //         'products.code as product_code',
    //         DB::raw('SUM(reports.bet_amount) as total_bet_amount'),
    //         DB::raw('SUM(reports.valid_bet_amount) as total_valid_bet_amount'),
    //         DB::raw('SUM(reports.payout_amount) as total_payout_amount'))
    //         ->groupBy('users.user_name', 'product_name', 'product_code')
    //         ->where('reports.member_name', $user_name)
    //         ->get();

    //     return view('report.view', compact('reports'));
    // }

    // // amk
    // private function makeJoinTable()
    // {
    //     $query = User::query()->roleLimited();
    //     $query->join('reports', 'reports.member_name', '=', 'users.user_name')
    //         ->join('products', 'reports.product_code', '=', 'products.code')
    //         ->join('game_lists', 'reports.game_name', '=', 'game_lists.code')
    //         ->where('reports.status', '101');

    //     return $query;
    // }

    // sophia
    // private function makeJoinTable()
    // {
    //     $query = User::query()->roleLimited();
    //     $query->join('reports', 'reports.member_name', '=', 'users.user_name')
    //         ->join('products', 'reports.product_code', '=', 'products.code')
    //         ->where('reports.status', '101');

    //     return $query;
    // }

    //     private function makeJoinTable()
    // {
    //     $query = User::query()->roleLimited();
    //     $query->join('reports', 'reports.member_name', '=', 'users.user_name')
    //           ->join('products', 'reports.product_code', '=', 'products.code')
    //           ->join('game_lists', 'products.product_id', '=', 'game_lists.product_id')
    //           ->select('reports.*', 'game_lists.code as game_code', 'game_lists.name as game_name')
    //           ->where('reports.status', 101);

    //     return $query;
    // }

    /*

    SELECT
        reports.*,
        game_lists.code AS game_code,
        game_lists.name AS game_name
    FROM
        reports
    JOIN
        game_lists ON reports.game_name = game_lists.code
    WHERE
        reports.status = '101';

    */