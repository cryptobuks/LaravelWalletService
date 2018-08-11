<?php

namespace Alive2212\LaravelWalletService;

use Alive2212\LaravelSmartRestful\BaseModel;
use Alive2212\LaravelWalletService\Observers\AliveWalletPaymentObserver;
use App\User;

class AliveWalletPayment extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payment_id',
        'author_id',
        'from',
        'to',
        'for',
        'amount',
        'balance',
        'extra_value',
        'description',
        'revoked',
        'locked',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function author()
    {
        return $this->belongsTo(
            User::class
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function from()
    {
        return $this->belongsTo(
            AliveWalletBase::class,
            'from'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function for()
    {
        return $this->belongsTo(
            AliveWalletStuff::class,
            'for'
        );
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function to()
    {
        return $this->belongsTo(
            AliveWalletBase::class,
            'to'
        );
    }

    /**
     * @return null
     */
    public function getQueueableRelations()
    {
        return null;
        // TODO: Implement getQueueableRelations() method.
    }

    /**
     * @param $from
     * @param $to
     * @return mixed
     */
    public function getLastPayment($from, $to)
    {
        $payment = $this->whereFromTo($from, $to);
        return $payment->orderBy('updated_at', 'DESC')->take(1)->first();
    }

    /**
     * @param $from
     * @param $to
     * @return mixed
     */
    public function getPaymentList($from, $to)
    {
        $payment = $this->whereFromTo($from, $to)
            ->with('author','from.user','to.user','for');
        return $payment->orderBy('updated_at', 'DESC')->get();
    }

    /**
     * @param $from
     * @param $to
     * @return int
     */
    public function getLastBalance($from, $to)
    {
        return $this->calcLastBalance($from, $to);
    }

    /**
     * @param $from
     * @param $to
     * @return int
     */
    public function calcLastBalance($from, $to)
    {
        $lastPaymentParams = $this->getLastPayment($from, $to);
        if (is_null($lastPaymentParams)) {
            return 0;
        }
        $lastPaymentParams = $lastPaymentParams->toArray();

        if ($lastPaymentParams['from'] == $from) {
            return $lastPaymentParams['balance'];
        }

        return -$lastPaymentParams['balance'];
    }

    /**
     * @param $from
     * @param $to
     * @return AliveWalletPayment
     */
    public function whereFromTo($from, $to)
    {
        $payment = new AliveWalletPayment();
        $payment = $payment->where([
            ['from', '=', $from],
            ['to', '=', $to],
            ['revoked', '=', 0],
        ])->orWhere([
            ['from', '=', $to],
            ['to', '=', $from],
            ['revoked', '=', 0],
        ]);
        return $payment;
    }

    /**
     * add observer to updating some field in "Saving"
     */
    protected static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub
        self::observe(AliveWalletPaymentObserver::class);
    }
}