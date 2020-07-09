<?php
namespace App\Library\Services;
  
class TransactionService
{
    public function creditBalance($data)
    {
      //Credit Account Balance
      $credit = \App\Balances::where('account_inr', $data->account_inr)->get()->first();
      $credit->balance = $data->amount + $credit->balance;
      $credit->save();

      $account = new \App\Transactions;
      $account->reference = uniqid();
      $account->account_inr = $data->account_inr;
      $account->amount = $data->amount;
      $account->save();
      return true;
    }

    public function debitBalance($data)
    {
      //Debit Account Balance
      $debit = \App\Balances::where('account_inr', $data->account_inr)->get()->first();
      if ($debit->balance >= $data->amount) {
        $debit->balance = $debit->balance - $data->amount;
        $debit->save();

        $account = new \App\Transactions;
        $account->reference = uniqid();
        $account->account_inr = $data->account_inr;
        $account->amount = $data->amount;
        $account->save();
        return true;
      }
      return false;
      

      
    }
}