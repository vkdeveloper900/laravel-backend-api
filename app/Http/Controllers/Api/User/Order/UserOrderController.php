<?php

namespace App\Http\Controllers\Api\User\Order;

use App\Http\Controllers\Controller;
use App\Jobs\SendPackagePurchasedNotification;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Models\UserPackage;
use App\Services\Payment\RazorpayPaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | STEP 1: BUY PACKAGE (CREATE ORDER ONLY)
    |--------------------------------------------------------------------------
    | - Order created
    | - Payment record created (pending)
    | - No gateway call yet
    |--------------------------------------------------------------------------
    */
    public function buy($id, Request $request)
    {
        $user = $request->user();
        $package = Package::where('status', 1)->findOrFail($id);

        DB::beginTransaction();

        try {

            // Prevent duplicate active package
            $alreadyActive = $user->userPackages()
                ->where('package_id', $package->id)
                ->where('status', 'active')
                ->where('expiry_date', '>', now())
                ->exists();

            if ($alreadyActive) {
                return response()->json([
                    'message' => 'You already have an active subscription.'
                ], 422);
            }

            $originalPrice = $package->price;
            $discountAmount = 0;
            $finalAmount = $originalPrice;

            // Create Order
            $order = Order::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'original_price' => $originalPrice,
                'discount_amount' => $discountAmount,
                'final_price' => $finalAmount,
                'status' => 'created',
            ]);

            // Create Payment record (initial state)
            Payment::create([
                'order_id' => $order->id,
                'gateway' => 'razorpay',
                'amount' => $finalAmount,
                'status' => 'initiated',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully.',
                'order_id' => $order->id,
                'amount' => $finalAmount,
            ]);

        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 2: INITIATE PAYMENT (CALL GATEWAY)
    |--------------------------------------------------------------------------
    | - Create Razorpay Order
    | - Update local order status to pending
    |--------------------------------------------------------------------------
    */
//    public function pay($orderId, RazorpayPaymentService $paymentService) todo setup pending
    public function pay($orderId)
    {
        $user = auth()->user();

        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->where('status', 'created')
            ->firstOrFail();

        // Create Razorpay order todo setup pending
//        $razorpayOrder = $paymentService->createOrder(
//            $order->final_price,
//            $order->order_number ?? 'ORD-' . $order->id
//        );

        $razorpayOrder['id'] = 'ORD-' . $order->id;


        // Update order status
        $order->update([
            'status' => 'pending',
            'gateway_name' => 'razorpay',
            'gateway_order_id' => $razorpayOrder['id'],
        ]);

        return response()->json([
            'message' => 'Payment initiated',
            'razorpay_order_id' => $razorpayOrder['id'],
            'amount' => $order->final_price,
            'key' => config('services.razorpay.key'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 3: VERIFY PAYMENT (AFTER SUCCESS CALLBACK)
    |--------------------------------------------------------------------------
    | - Verify Razorpay signature
    | - Mark order as paid
    | - Update payment
    | - Assign package to user
    |--------------------------------------------------------------------------
    */
   // public function verify(Request $request, $orderId, RazorpayPaymentService $paymentService) todo setup pending
    public function verify(Request $request, $orderId)
    {
        $user = auth()->user();

        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        DB::beginTransaction();

        try {

            // Verify Razorpay signature
//            $paymentService->verifySignature([
//                'razorpay_order_id' => $request->razorpay_order_id,
//                'razorpay_payment_id' => $request->razorpay_payment_id,
//                'razorpay_signature' => $request->razorpay_signature,
//            ]);

            // Update Order
            $order->update([
                'status' => 'paid',
            ]);

            // Update Payment
            $payment = $order->payments()->latest()->first();
            $payment->update([
                'status' => 'success',
                'gateway_payment_id' => $request->razorpay_payment_id,
            ]);

            /*
            |--------------------------------------------------------------------------
            | ASSIGN PACKAGE TO USER
            |--------------------------------------------------------------------------
            */
            UserPackage::create([
                'user_id' => $user->id,
                'package_id' => $order->package_id,
                'order_id' => $order->id,
                'activated_at' => now(),
                'expiry_date' => now()->addDays(30),
                'status' => 'active',
            ]);

            DB::commit();

            SendPackagePurchasedNotification::dispatch($order->id);

            return response()->json([
                'message' => 'Payment successful. Package activated.'
            ]);

        } catch (Exception $e) {

            DB::rollBack();

            $order->update(['status' => 'failed']);

            return response()->json([
                'message' => 'Payment verification failed'
            ], 400);
        }
    }
}

