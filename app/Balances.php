<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Balances extends Model
{
    protected $table = 'balances';

    protected $fillable = ['account_nr', 'balance', 'amount'];
}
