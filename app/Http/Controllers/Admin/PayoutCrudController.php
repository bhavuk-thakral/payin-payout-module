<?php

namespace App\Http\Controllers\Admin;

use App\Models\Payout;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class PayoutCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Payout::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/payout');
        CRUD::setEntityNameStrings('payout', 'payouts');
    }

    protected function setupListOperation()
    {
        CRUD::column('transaction_id');
        CRUD::column('merchant.name')->label('Merchant')->type('text');
        CRUD::column('amount');
        CRUD::column('currency');
        CRUD::column('status');
        CRUD::column('created_at')->type('datetime');

        if ($status = request('status')) {
            $this->crud->addClause('where', 'status', $status);
        }
        if ($merchantId = request('merchant_id')) {
            $this->crud->addClause('where', 'merchant_id', $merchantId);
        }
        if ($dateFrom = request('date_from')) {
            $this->crud->addClause('whereDate', 'created_at', '>=', $dateFrom);
        }
        if ($dateTo = request('date_to')) {
            $this->crud->addClause('whereDate', 'created_at', '<=', $dateTo);
        }
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
        CRUD::column('beneficiary_name');
        CRUD::column('beneficiary_account');
        CRUD::column('ifsc_code');
        CRUD::column('wallet_debited')->type('boolean');
        CRUD::column('wallet_reversed')->type('boolean');
        CRUD::column('attempts');
        CRUD::column('processed_at')->type('datetime');
    }
}