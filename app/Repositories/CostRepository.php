<?php

namespace App\Repositories;

use App\Models\Cost;
use App\Models\ExtraCost;
use Carbon\Carbon;
use InvalidArgumentException;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class CostRepository
{
    protected $cost;
    protected $extraCost;

    public function __construct(Cost $cost, ExtraCost $extraCost)
    {
        $this->cost = $cost;
        $this->extraCost = $extraCost;
    }

    /**
     * save cost
     *
     * @param array $data
     * @return Cost
     */
    public function saveCostRepo($data = [])
    {
        $config = [
            'table' => 'costs',
            'length' => 12,
            'field' => 'number',
            'prefix' => 'I'.Carbon::now('Asia/Jakarta')->format('ymd'),
            'reset_on_prefix_change' => true
        ];
        $cost = new $this->cost;
        $cost->pickup_id = $data['pickupId'];
        $cost->amount = $data['amount'];
        $cost->clear_amount = $data['clearAmount'];
        $cost->discount = $data['discount'];
        $cost->service = $data['service'];
        $cost->amount_with_service = $data['amountWithService'];
        $cost->tax_rate = $data['taxRate'];
        $cost->tax_amount = $data['taxAmount'];
        $cost->insurance_amount = $data['insuranceAmount'];
        $cost->amount_with_tax = $data['amountWithTax'];
        $cost->amount_with_insurance = $data['amountWithInsurance'];
        $cost->amount_with_tax_insurance = $data['amountWithTaxInsurance'];
        $cost->number = IdGenerator::generate($config);
        $cost->method = 'Tempo';
        $cost->due_date = Carbon::now('Asia/Jakarta')->addHours(24);
        $cost->status = 'Hutang';
        $cost->save();
        return $cost;
    }

    /**
     * update payment method
     */
    public function updatePaymentMethod($data = [])
    {
        $cost = $this->cost->find($data['id']);
        if (strtolower($data['method']) == 'tempo') {
            $cost->due_date = $data['dueDate'];
        }
        $cost->method = $data['method'];
        $cost->save();
        return $cost;
    }

    /**
     * update amount cost by pickup
     *
     * @param array $data
     * @return Cost
     */
    public function updateOrCreateCostByPickupIdRepo($data)
    {
        // $cost = $this->cost->updateOrCreate(
        //     [
        //         'pickup_id' => $data['pickupId']
        //     ],
        //     [
        //         'amount' => $data['amount'],
        //         'clear_amount' => $data['clearAmount'],
        //         'discount' => $data['discount'],
        //         'service' => $data['service'],
        //         'amount_with_service' => $data['amountWithService'],
        //     ]
        // );
        // return $cost;
        $cost = $this->cost->where('pickup_id', $data['pickupId']);
        if (!$cost->first()) {
            $config = [
                'table' => 'costs',
                'length' => 12,
                'field' => 'number',
                'prefix' => 'I'.Carbon::now('Asia/Jakarta')->format('ymd'),
                'reset_on_prefix_change' => true
            ];
            $cost = new $this->cost;
            $cost->amount = $data['amount'];
            $cost->pickup_id = $data['pickupId'];
            $cost->clear_amount = $data['clearAmount'];
            $cost->discount = $data['discount'];
            $cost->service = $data['service'];
            $cost->amount_with_service = $data['amountWithService'];
            $cost->amount_with_tax = $data['amountWithTax'];
            $cost->amount_with_insurance = $data['amountWithInsurance'];
            $cost->amount_with_tax_insurance = $data['amountWithTaxInsurance'];
            $cost->number = IdGenerator::generate($config);
            $cost->method = 'Tempo';
            $cost->due_date = Carbon::now('Asia/Jakarta')->addHours(24);
            $cost->status = 'Hutang';
            $cost->save();
            return $cost;
        }
        $cost->update(
            [
                'amount' => $data['amount'],
                'clear_amount' => $data['clearAmount'],
                'discount' => $data['discount'],
                'service' => $data['service'],
                'amount_with_service' => $data['amountWithService'],
                'amount_with_tax' => $data['amountWithTax'],
                'amount_with_insurance' => $data['amountWithInsurance'],
                'amount_with_tax_insurance' => $data['amountWithTaxInsurance']
            ]
        );
        $result = $cost->first();
        return $result;
    }

    /**
     * update cost by pickup
     *
     * @param array $data
     * @return Cost
     */
    public function updateCostByPickupIdRepo($data = [])
    {
        return $this->cost->where('pickup_id', $data['pickupId'])->update([
            'amount' => $data['amount'],
            'method' => $data['method'],
            'due_date' => $data['dueDate'],
            'discount' => $data['discount'],
            'service' => $data['service'],
            'clear_amount' => $data['clearAmount'],
            'amount_with_service' => $data['amountWithService'],
            'tax_rate' => $data['taxRate'],
            'tax_amount' => $data['taxAmount'],
            'insurance_amount' => $data['insuranceAmount'],
            'amount_with_tax' => $data['amountWithTax'],
            'amount_with_insurance' => $data['amountWithInsurance'],
            'amount_with_tax_insurance' => $data['amountWithTaxInsurance']
        ]);
    }

    /**
     * update cost repo
    */
    public function updateCostRepo($data = [])
    {
        $cost = $this->cost->find($data['id']);
        $cost->amount = $data['amount'];
        $cost->method = $data['method'];
        $cost->due_date = $data['dueDate'];
        $cost->discount = $data['discount'];
        $cost->service = $data['service'];
        $cost->clear_amount = $data['clearAmount'];
        $cost->status = ucwords($data['status']);
        $cost->notes = $data['notes'];
        $cost->amount_with_service = $data['amountWithService'];
        $cost->tax_rate = $data['taxRate'];
        $cost->tax_amount = $data['taxAmount'];
        $cost->insurance_amount = $data['insuranceAmount'];
        $cost->amount_with_tax = $data['amountWithTax'];
        $cost->amount_with_insurance = $data['amountWithInsurance'];
        $cost->amount_with_tax_insurance = $data['amountWithTaxInsurance'];
        $cost->save();
        return $cost;
    }

    /**
     * update extra costs
     */
    public function updateExtraCostRepo($data = [])
    {
        $extraCost = $this->extraCost->find($data['id']);
        $extraCost->amount = $data['amount'];
        $extraCost->notes = $data['notes'];
        $extraCost->save();
        return $extraCost;
    }

    /**
     * save extra cost
     */
    public function saveExtraCostRepo($data = [])
    {
        $extraCost = new $this->extraCost;
        $extraCost->cost_id = $data['costId'];
        $extraCost->amount = $data['amount'];
        $extraCost->notes = $data['notes'];
        $extraCost->created_by = $data['userId'];
        $extraCost->updated_by = $data['userId'];
        $extraCost->save();
        return $extraCost;
    }

    /**
     * delete extra cost by cost id
     */
    public function deleteExtraCostByCostIdRepo($costId)
    {
        $this->extraCost->where('cost_id', $costId)->delete();
    }

    /**
     * save insurance and tax by pickupId
     */
    public function saveInsuranceAndTax($data = [])
    {
        $config = [
            'table' => 'costs',
            'length' => 12,
            'field' => 'number',
            'prefix' => 'I'.Carbon::now('Asia/Jakarta')->format('ymd'),
            'reset_on_prefix_change' => true
        ];
        $cost = new $this->cost;
        $cost->pickup_id = $data['pickupId'];
        $cost->tax_rate = $data['taxRate'];
        $cost->tax_amount = $data['taxAmount'];
        $cost->insurance_amount = $data['insuranceAmount'];
        $cost->amount_with_tax = $data['amountWithTax'];
        $cost->amount_with_insurance = $data['amountWithInsurance'];
        $cost->amount_with_tax_insurance = $data['amountWithTaxInsurance'];
        $cost->number = IdGenerator::generate($config);
        $cost->method = 'Tempo';
        $cost->due_date = Carbon::now('Asia/Jakarta')->addHours(24);
        $cost->status = 'Hutang';
        $cost->save();
        return $cost;
    }

    /**
     * save or update cost
     *
     * @param array $data
     * @return Cost
     */
    public function saveOrUpdateCostRepo($data = [])
    {
        // $cost = $this->cost->updateOrCreate(
        //     ['pickup_id' => $data['pickupId']],
        //     [
        //         'amount' => $data['amount'],
        //         'clear_amount' => $data['clearAmount'],
        //         'discount' => $data['discount'],
        //         'service' => $data['service'],
        //         'amount_with_service' => $data['amountWithService'],
        //         'tax_rate' => $data['taxRate'],
        //         'tax_amount' => $data['taxAmount'],
        //         'insurance_amount' => $data['insuranceAmount'],
        //         'amount_with_tax' => $data['amountWithTax'],
        //         'amount_with_insurance' => $data['amountWithInsurance'],
        //         'amount_with_tax_insurance' => $data['amountWithTaxInsurance']
        //     ]
        // );
        $cost = $this->cost->where('pickup_id', $data['pickupId']);
        if (!$cost->first()) {
            $config = [
                'table' => 'costs',
                'length' => 12,
                'field' => 'number',
                'prefix' => 'I'.Carbon::now('Asia/Jakarta')->format('ymd'),
                'reset_on_prefix_change' => true
            ];
            $cost = new $this->cost;
            $cost->pickup_id = $data['pickupId'];
            $cost->amount = $data['amount'];
            $cost->clear_amount = $data['clearAmount'];
            $cost->discount = $data['discount'];
            $cost->service = $data['service'];
            $cost->amount_with_service = $data['amountWithService'];
            $cost->tax_rate = $data['taxRate'];
            $cost->tax_amount = $data['taxAmount'];
            $cost->insurance_amount = $data['insuranceAmount'];
            $cost->amount_with_tax = $data['amountWithTax'];
            $cost->amount_with_insurance = $data['amountWithInsurance'];
            $cost->amount_with_tax_insurance = $data['amountWithTaxInsurance'];
            $cost->number = IdGenerator::generate($config);
            $cost->method = 'Tempo';
            $cost->due_date = Carbon::now('Asia/Jakarta')->addHours(24);
            $cost->status = 'Hutang';
            $cost->save();
            return $cost;
        }
        $cost->update(
            [
                'amount' => $data['amount'],
                'clear_amount' => $data['clearAmount'],
                'discount' => $data['discount'],
                'service' => $data['service'],
                'amount_with_service' => $data['amountWithService'],
                'tax_rate' => $data['taxRate'],
                'tax_amount' => $data['taxAmount'],
                'insurance_amount' => $data['insuranceAmount'],
                'amount_with_tax' => $data['amountWithTax'],
                'amount_with_insurance' => $data['amountWithInsurance'],
                'amount_with_tax_insurance' => $data['amountWithTaxInsurance']
            ]
        );
        $result = $this->cost->where('pickup_id', $data['pickupId'])->with('extraCosts')->first();
        return $result;
    }

    /**
     * get cost by pickup id
     */
    public function getByPickup($pickupId)
    {
        $cost = $this->cost->where('pickup_id', $pickupId)->first();
        return $cost;
    }

    /**
     * get by pickup number
     */
    public function getByPickupNumberRepo($number)
    {
        $cost = $this->cost->whereHas('pickup', function($q) use ($number) {
            $q->where('number', $number);
        })->first();
        return $cost;
    }

    /**
     * set amount to zero by POP
     */
    public function setAmountToZeroByPopRepo($popId)
    {
        $cost = $this->cost->whereHas('pickup', function($q) use ($popId) {
            $q->whereHas('proofOfPickup', function($p) use ($popId) {
                $p->where('id', $popId);
            });
        });
        $costData = $cost->first();
        $tempCost = [
            'amount' => $costData->amount,
            'service' => $costData->service,
            'clearAmount' => $costData->clear_amount,
            'amountWithService' => $costData->amount_with_service,
            'taxRate' => $costData->tax_rate,
            'taxAmount' => $costData->tax_amount,
            'insuranceAmount' => $costData->insurance_amount,
            'amountWithTax' => $costData->amount_with_tax,
            'amountWithInsurance' => $costData->amount_with_insurance,
            'amountWithTaxInsurance' => $costData->amount_with_tax_insurance
        ];
        $extraCosts = $costData->extraCosts;
        if (count($extraCosts) > 0) {
            $this->extraCost->where('cost_id', $costData->id)->delete();
        }
        $cost->update([
            'amount' => 0,
            'clear_amount' => 0,
            'amount_with_service' => $tempCost['service'],
            'tax_amount' => 0,
            'amount_with_tax' => 0,
            'amount_with_insurance' => $tempCost['insuranceAmount'],
            'amount_with_tax_insurance' => $tempCost['insuranceAmount']
        ]);
        return $cost;
    }

    /**
     * set amount by pickup
     */
    public function setAmountToZeroByPickupRepo($pickupId)
    {
        $cost = $this->cost->where('pickup_id', $pickupId);
        $costData = $cost->first();
        $tempCost = [
            'amount' => $costData->amount,
            'service' => $costData->service,
            'clearAmount' => $costData->clear_amount,
            'amountWithService' => $costData->amount_with_service,
            'taxRate' => $costData->tax_rate,
            'taxAmount' => $costData->tax_amount,
            'insuranceAmount' => $costData->insurance_amount,
            'amountWithTax' => $costData->amount_with_tax,
            'amountWithInsurance' => $costData->amount_with_insurance,
            'amountWithTaxInsurance' => $costData->amount_with_tax_insurance
        ];
        $extraCosts = $costData->extraCosts;
        if (count($extraCosts) > 0) {
            $this->extraCost->where('cost_id', $costData->id)->delete();
        }
        $cost->update([
            'amount' => 0,
            'clear_amount' => 0,
            'amount_with_service' => $tempCost['service'],
            'tax_amount' => 0,
            'amount_with_tax' => 0,
            'amount_with_insurance' => $tempCost['insuranceAmount'],
            'amount_with_tax_insurance' => $tempCost['insuranceAmount']
        ]);
        return $cost;
    }
}
