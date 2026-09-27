<?php

namespace App\Http\Controllers\Admin;

use App\Models\Wallet;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class WalletCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Wallet::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/wallet');
        CRUD::setEntityNameStrings('wallet', 'wallets');
    }

    protected function setupListOperation()
    {
        CRUD::column('merchant.name')->label('Merchant')->type('text');
        CRUD::column('balance');
        CRUD::column('locked_balance');
        CRUD::column('currency');
        CRUD::column('updated_at')->type('datetime');

        // Basic filter: /admin/wallet?merchant_id=1
        if ($merchantId = request('merchant_id')) {
            $this->crud->addClause('where', 'merchant_id', $merchantId);
        }
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }
}