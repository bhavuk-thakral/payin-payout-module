{{-- This file is used to store sidebar items, inside the Backpack admin panel --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<li class="nav-item"><a class="nav-link" href="{{ backpack_url('merchant') }}"><i class="la la-store nav-icon"></i> Merchants</a></li>

<li class="nav-item"><a class="nav-link" href="{{ backpack_url('payin') }}"><i class="la la-arrow-circle-down nav-icon"></i> Pay-Ins</a></li>

<li class="nav-item"><a class="nav-link" href="{{ backpack_url('payout') }}"><i class="la la-arrow-circle-up nav-icon"></i> Payouts</a></li>

<li class="nav-item"><a class="nav-link" href="{{ backpack_url('wallet') }}"><i class="la la-wallet nav-icon"></i> Wallets</a></li>