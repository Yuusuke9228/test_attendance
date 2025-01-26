<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index()
    {
        $customer = Customer::paginate(100);
        return Inertia::render('Admin/Master/Customers/CustomerIndex', compact('customer'));
    }
    public function create()
    {
        return Inertia::render('Admin/Master/Customers/CreateCustomer');
    }
    public function store(CustomerRequest $request)
    {
        if ($request->email) {
            $request->validate(
                ['email' => 'string|email'],
                ['email.email' => 'メール形式が正しくありません。'],
            );
        }
        if ($request) {
            $customer =  Customer::create([
                'customer_name'         => $request->name,
                'customer_person'       => $request->person,
                'customer_saluation'    => $request->saluation,
                'customer_email'        => $request->email,
                'customer_tel'          => $request->tel,
                'customer_fax'          => $request->fax,
                'customer_zip_code'     => $request->zipCode,
                'customer_address_1'    => $request->address_1,
                'customer_address_2'    => $request->address_2,
                'customer_memo'         => $request->memo,
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.customer.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.customer.show', ['id' => $customer->id]);
            } else {
                return redirect()->route('admin.master.customer.create');
            }
        }
    }
    public function show($id)
    {
        $customerDetail = Customer::find($id);
        return Inertia::render('Admin/Master/Customers/CustomerDetail', compact('customerDetail'));
    }
    public function edit($id)
    {
        $customerDetail = Customer::find($id);
        return Inertia::render('Admin/Master/Customers/CustomerEdit', compact('customerDetail'));
    }
    public function update(CustomerRequest $request)
    {
        if ($request->email) {
            $request->validate(
                ['email' => 'string|email'],
                ['email.email' => 'メール形式が正しくありません。'],
            );
        }
        if ($request) {
            Customer::where('id', $request->id)->update([
                'customer_name'         => $request->name,
                'customer_person'       => $request->person,
                'customer_saluation'    => $request->saluation,
                'customer_email'        => $request->email,
                'customer_tel'          => $request->tel,
                'customer_fax'          => $request->fax,
                'customer_zip_code'     => $request->zipCode,
                'customer_address_1'    => $request->address_1,
                'customer_address_2'    => $request->address_2,
                'customer_memo'         => $request->memo,
            ]);

            $redirect_option = $request->redirectOption;
            if ($redirect_option == 1) {
                return redirect()->route('admin.master.customer.index');
            } else if ($redirect_option == 2) {
                return redirect()->route('admin.master.customer.show', ['id' => $request->id]);
            } else {
                return redirect()->route('admin.master.customer.create');
            }
        }
    }
    public function destroy(Request $request)
    {
        if ($request) {
            Customer::where('id', $request->id)->delete();
            return redirect()->route('admin.master.customer.index');
        }
    }
}
