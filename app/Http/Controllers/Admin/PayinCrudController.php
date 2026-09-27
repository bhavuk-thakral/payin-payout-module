<?php

namespace App\Http\Controllers\Admin;

use App\Models\Payin;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class PayinCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Payin::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/payin');
        CRUD::setEntityNameStrings('pay-in', 'pay-ins');
    }

    protected function setupListOperation()
    {
        CRUD::column('transaction_id');
        CRUD::column('merchant.name')->label('Merchant')->type('text');
        CRUD::column('amount');
        CRUD::column('currency');
        CRUD::column('status');
        CRUD::column('created_at')->type('datetime');

        // Basic filters via query string, e.g.:
        // /admin/payin?status=PENDING
        // /admin/payin?merchant_id=1
        // /admin/payin?date_from=2026-09-01&date_to=2026-09-30
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
        CRUD::column('customer_name');
        CRUD::column('customer_email');
        CRUD::column('payment_method');
        CRUD::column('wallet_credited')->type('boolean');
        CRUD::column('attempts');
        CRUD::column('processed_at')->type('datetime');
    }
}