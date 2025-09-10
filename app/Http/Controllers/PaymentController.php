<?php

namespace App\Http\Controllers;

use App\Http\Controllers\PaymentController;
use App\Models\Transaction;
use App\Services\EnkapService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // Inject the EnkapService for API communication
    protected EnkapService $enkap;

    public function __construct(EnkapService $enkap)
    {
        $this->enkap = $enkap;
    }

    /**
     * Initiate a new payment order.
     * - Saves transaction to DB
     * - Calls Enkap API to create order
     * - Returns Enkap API response
     */
    public function initiate(Request $request)
    {
        // Validate input
        // $request->validate([
        //     'customer_name' => 'required|string|max:255',
        //     'description'  => 'nullable|string|max:255',
        //     'email'        => 'nullable|email|max:255',
        //     'phone_number'  => 'nullable|string|max:20', // Adjust as needed
        //     'lang_key'      => 'nullable|string|size:2',
        //     'total_amount'  => 'required|numeric|min:0.01',
        //     'items'        => 'nullable|array',
        //     'items.*.name' => 'required_with:items|string|max:255',
        //     'items.*.quantity' => 'required_with:items|integer|min:1',
        //     'items.*.price'    => 'required_with:items|numeric|min:0.01',
        // ]);

        // Save transaction in DB

        $merchantRef = uniqid("ref_");

        // Save transaction in the database
        $transaction = Transaction::create([
            'currency'           => $request->input('currency', 'XAF'),
            'customer_name'       => $request->input('customerName'),
            'description'        => $request->input('description'),
            'email'              => $request->input('email'),
            'phone_number'        => $request->input('phoneNumber'),
            'lang_key'            => $request->input('langKey', 'en'),
            'merchant_reference_id'  => $merchantRef,
            // 'optRefOne'          => $request->input('optRefOne'),
            // 'optRefTwo'          => $request->input('optRefTwo'),
            'expiry_date'         => $request->input('expiryDate'),
            'order_date'          => $request->input('orderDate'),
            'total_amount'        => $request->input('totalAmount'),
            'items'              => $request->input('items'),
            "redirect_url"        => config('services.enkap.return_url'),
            'status'             => 'CREATED',
            'payment_provider'    => 'ENKAP', // default for now
        ]);

        // Prepare order data for Enkap API
        $order = [
            "currency"          => "XAF",
            "customerName"      => $request->input('customerName'),
            "description"       => $request->input('description'),
            "email"             => $request->input('email'),
            "items"             => $request->input('items', []),
            "langKey"           => $request->input('langKey', 'en'),
            "merchantReference" => $merchantRef,
            "phoneNumber"       => $request->input('phoneNumber'),
            "redirectUrl"        => $request->input('redirectUrl'),
            "totalAmount"       => $request->input('totalAmount'),
        ];

        // Call Enkap API to create the order
        $response = $this->enkap->createOrder($order);

        // Update transaction with Enkap order transaction ID if available
        if (isset($response['orderTransactionId'])) {
            $transaction->update(['order_transaction_id' => $response['orderTransactionId']]);
        }

        // Return API response with appropriate status code
        return response()->json(
            $response,
            isset($response['redirectUrl']) && $response['redirectUrl'] ? 201 : 400
        );
    }

    /**
     * Handle return from payment gateway.
     * - Fetches transaction and Enkap order details
     * - Updates transaction status
     * - Returns status and Enkap response
     */
    public function return(Request $request, $referenceId)
    {
        // Get order details from Enkap
        $transaction = Transaction::where('merchant_reference_id', $referenceId)->firstOrFail();

        $response = $this->enkap->getDetails(['orderMerchantId' => $referenceId]);
        $status = $response['status'] ?? $transaction->status;

        // Update transaction status
        $transaction->update(['status' => $status]);

        // Return status and Enkap response
        return response()->json([
            'reference' => $referenceId,
            'status'    => $status,
            'enkap'     => $response,
        ]);
    }

    /**
     * Handle payment callback from Enkap.
     * - Updates transaction status based on callback data
     */
    public function callback(Request $request, $referenceId)
    {

    // Find transaction by merchantReference
    $transaction = Transaction::where('merchant_reference_id', $referenceId)->first();

    if (!$transaction) {
        return response()->json(['error' => 'Transaction not found'], 404);
    }

    // Extract status from Enkap callback
    $status = $request->input('status');

    dd($status);
    $transaction->update([
        'status'                => $status,
        'enkap_transaction_id'  => $request->input('orderTransactionId', $transaction->enkap_transaction_id),
        'paymentProvider'       => $request->input('paymentMethod', $transaction->paymentProvider),
    ]);

    // Respond back to Enkap
    return response()->json([
        'message'     => 'Callback processed successfully',
        'referenceId' => $transaction->merchantReference,
        'status'      => $transaction->status,
    ], 200);
    }

    /**
     * Get payment status.
     * - Fetches transaction and Enkap status
     * - Updates transaction status if needed
     * - Returns status and Enkap response
     */
    public function status(Request $request, $referenceId)
    {
        // Find transaction by merchant reference
        $transaction = Transaction::where('merchant_reference_id', $referenceId)->firstOrFail();

        // Get status from Enkap API
        $response = $this->enkap->getStatus(['orderMerchantId' => $referenceId]);

        // Update transaction status if available
        if (isset($response['status'])) {
            $transaction->update(['status' => $response['status']]);
        }

        // Return status and Enkap response
        return response()->json([
            'reference' => $referenceId,
            'status'    => $transaction->status,
            'enkap'     => $response,
        ]);
    }
}
