<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FortunePayment;
use Illuminate\Http\Request;

class FortunePayPaymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = max(1, (int) $request->query('per_page', 20));
        $payments = FortunePayment::query()
            ->latest('id')
            ->paginate($perPage)
            ->appends(['per_page' => $perPage]);

        return \view('admin.fortune.payments.index', [
            'payments' => $payments,
        ]);
    }
}