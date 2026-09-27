<?php

namespace App\Http\Controllers\Admin;

use App\Models\Merchant;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class MerchantCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Merchant::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/merchant');
        CRUD::setEntityNameStrings('merchant', 'merchants');
    }

    protected function setupListOperation()
    {
        CRUD::column('merchant_code');
        CRUD::column('name');
        CRUD::column('email');
        CRUD::column('status')->type('text');
        CRUD::column('created_at')->type('datetime');

        // Basic filter via query string: /admin/merchant?status=active
        if ($status = request('status')) {
            $this->crud->addClause('where', 'status', $status);
        }
    }

    protected function setupCreateOperation()
    {
        CRUD::field('name');
        CRUD::field('email');
        CRUD::field('phone');
        CRUD::field('status')->type('select_from_array')->options([
            'active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended',
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        CRUD::column('merchant_code');
        CRUD::column('name');
        CRUD::column('email');
        CRUD::column('phone');
        CRUD::column('api_key');
        CRUD::column('status');
        CRUD::column('created_at')->type('datetime');
    }
}