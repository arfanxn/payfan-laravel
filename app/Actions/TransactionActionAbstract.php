<?php

namespace App\Actions;

use App\Models\UserWallet;
use Illuminate\Support\Facades\Auth;

abstract class TransactionActionAbstract
{
    protected  $amount, $charge, $note,  $fromWallet, $toWallet;

    public function setAmount(float $amount)
    {
        $this->amount = $amount;
        return $this;
    }

    public function setCharge(float $charge)
    {
        $this->charge = $charge;
        return $this;
    }

    public function setNote(string $note = "")
    {
        $this->note = $note;
        return $this;
    }

    public function setFromWallet(UserWallet|string|null $fromWallet = null) //wallet object or address
    {
        if (is_null($fromWallet)) $this->fromWallet = Auth::user()->wallet->address;
        elseif ($fromWallet instanceof UserWallet) $this->fromWallet = $fromWallet->address;
        elseif ($fromWallet) $this->fromWallet = $fromWallet;

        return $this;
    }

    public function setToWallet(UserWallet|string $toWallet) //wallet object or address
    {
        if ($toWallet instanceof UserWallet) $this->toWallet = $toWallet->address;
        elseif (is_string($toWallet)) $this->toWallet = $toWallet;

        return $this;
    }
}
