<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->string('status')->toString(),
            'search' => $request->string('search')->toString(),
            'source' => $request->string('source')->toString(),
            'review_required' => $request->boolean('review_required'),
        ];

        return Inertia::render('Payments/Index', [
            'filters' => $filters,
            'payments' => Payment::query()
                ->with('order:id,order_number,customer_name,customer_email,customer_phone')
                ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
                ->when($filters['source'], fn ($query, $source) => $query->where('source', $source))
                ->when($filters['review_required'], fn ($query) => $query->where('status', 'review_required'))
                ->when($filters['search'], function ($query, $search) {
                    $query->where(function ($searchQuery) use ($search) {
                        $searchQuery->where('external_reference', 'like', "%{$search}%")
                            ->orWhere('provider_reference', 'like', "%{$search}%")
                            ->orWhere('provider_transaction_id', 'like', "%{$search}%")
                            ->orWhereHas('order', fn ($orderQuery) => $orderQuery
                                ->where('order_number', 'like', "%{$search}%")
                                ->orWhere('customer_name', 'like', "%{$search}%")
                                ->orWhere('customer_email', 'like', "%{$search}%"));
                    });
                })
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'providerConfig' => [
                'provider' => 'paystack',
                'currency' => config('paystack.currency'),
                'public_key_configured' => filled(config('paystack.public_key')),
                'secret_key_configured' => filled(config('paystack.secret_key')),
            ],
        ]);
    }
}
