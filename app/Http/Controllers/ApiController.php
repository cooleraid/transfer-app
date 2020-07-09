<?php

namespace App\Http\Controllers;

use App\Balances;
use App\Library\Services\TransactionService;
use Illuminate\Http\Request;
use stdClass;
use Redis;

class ApiController extends Controller
{
    public function transfer(Request $request, TransactionService $transaction)
    {
        //Begin Transaction
        \DB::beginTransaction();
        try {
            if (!$request->request_id) {
                return response()->json([
                "message" => "Request ID is required"
            ], 401);
            }
            if (!$request->amount || !is_numeric($request->amount)) {
                return response()->json([
                "message" => !is_numeric($request->amount) ? "Amount should be numeric" : "Amount is required"
            ], 401);
            }
            if (!Balances::where('account_inr', $request->from)->exists()) {
                return response()->json([
                "message" => "From account not found"
            ], 404);
            }
            if (!Balances::where('account_inr', $request->to)->exists()) {
                return response()->json([
                "message" => "To account not found"
            ], 404);
            }
            $fetch_request_id = Redis::get('request_id');
            if (!$fetch_request_id) {
                Redis::set('request_id', json_encode([$request->request_id]));
            } else {
                $fetch_request_id = json_decode($fetch_request_id);
            
                if (in_array($request->request_id, $fetch_request_id)) {
                    return response()->json([
                    "message" => "Duplicate Transaction Detected."
                ], 400);
                } else {
                    array_push($fetch_request_id, $request->request_id);
                    Redis::set('request_id', json_encode($fetch_request_id));
                }
            }

            $data = new stdClass;
            $data->amount = $request->amount;
            $data->account_inr = $request->from;
            $debit = $transaction->debitBalance($data);
            if (!$debit) {
                return response()->json([
                "message" => "Insufficient Balance",
                "data" => false
            ], 400);
            }
            $data->account_inr = $request->to;
            $transaction->creditBalance($data);

            //Commit Transaction
            \DB::commit();
            return response()->json([
            "message" => "Transfer successfully",
            "data" => true
        ], 200);
        } catch (\Illuminate\Database\QueryException $e) {

            //Rollback Transaction
            \DB::rollBack();
            \Log::listen('Log message', array('context'=>'Database Error', 'err'=>$e));
            return response(['message'=>'An error was encountered'], 500);
        }
    }
    public function createAccount()
    {
        $balance = new Balances;
        $balance->account_inr = hexdec(uniqid());
        $balance->save();
    
        return response()->json([
            "message" => "User balance created",
            "data" => $balance
        ], 201);
    }

    public function updateAccount(Request $request, $id, TransactionService $transaction)
    {
        if (!Balances::where('account_inr', $id)->exists()) {
            return response()->json([
                "message" => "Account not found"
            ], 404);
        }
        $data = new stdClass;
        $data->account_inr = $id;
        $data->amount = $request->amount;
        $transaction->creditBalance($data);
        return response()->json([
            "message" => "Account updated successfully",
            "data" => true
        ], 200);
    }

    public function fetchAccount($id)
    {
        if (!Balances::where('account_inr', $id)->exists()) {
            return response()->json([
            "message" => "Account not found"
            ], 404);
        }
        $account = Balances::where('account_inr', $id)->get();
        return response([
            "message" => "Account retrieved successfully",
            "data" => $account
        ], 200);
    }

    public function fetchAccounts()
    {
        $accounts = Balances::get();
        return response([
            "message" => "Accounts retrieved successfully",
            "data" => $accounts
        ], 200);
    }

    public function deleteAccount($id)
    {
        if (!Balances::where('id', $id)->exists()) {
            return response()->json([
                "message" => "Account not found"
            ], 404);
        }
        $account = Balances::find($id);
        $account->delete();
  
        return response()->json([
            "message" => "Account deleted successfully"
        ], 202);
    }
}
