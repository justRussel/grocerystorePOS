<?php
/**
 * GroceryPOS - SalesService
 * Core checkout business logic.
 */

class SalesService
{
    /**
     * Process a full checkout: validate → calculate → persist → deduct stock.
     *
     * @param  array $cartItems    Each: product_id, qty, selling_price, cost_price, name
     * @param  array $paymentInfo  Keys: payment_method, cash_tendered, discount_value, discount_type
     * @return array  {success, transaction_id, receipt_no, change_amount} | {success:false, error}
     */
    public static function processCheckout(array $cartItems, array $paymentInfo): array
    {
        if (empty($cartItems)) {
            return ['success' => false, 'error' => 'Cart is empty.'];
        }

        $pdo = Database::getInstance();

        // Step 1: Validate stock
        foreach ($cartItems as $item) {
            $product = Product::findById((int) $item['product_id']);
            if (!$product || !$product['is_active']) {
                return ['success' => false, 'error' => 'Product not found or inactive.'];
            }
            if ((int) $product['stock_qty'] < (int) $item['qty']) {
                return [
                    'success' => false,
                    'error'   => 'Insufficient stock for: ' . htmlspecialchars($product['name']),
                ];
            }
        }

        // Step 2: Calculate totals
        $cartResult = self::calculateCart(
            $cartItems,
            (float) ($paymentInfo['discount_value'] ?? 0),
            $paymentInfo['discount_type'] ?? 'fixed'
        );

        // Step 3: Validate payment
        $paymentMethod = $paymentInfo['payment_method'] ?? 'cash';
        $cashTendered  = (float) ($paymentInfo['cash_tendered'] ?? 0);
        $changeAmount  = 0.00;

        if ($paymentMethod === 'cash') {
            if ($cashTendered < $cartResult['total_amount']) {
                return ['success' => false, 'error' => 'Insufficient cash tendered.'];
            }
            $changeAmount = round($cashTendered - $cartResult['total_amount'], 2);
        }

        // Step 4: Persist inside a DB transaction
        try {
            $pdo->beginTransaction();

            $receiptNo     = Transaction::generateReceiptNo();
            $cashierId     = $_SESSION['user_id'] ?? null;

            // Build items array for insert
            $lineItems = [];
            foreach ($cartItems as $item) {
                $product     = Product::findById((int) $item['product_id']);
                $lineItems[] = [
                    'product_id'              => $item['product_id'],
                    'product_name_snapshot'   => $product['name'],
                    'cost_price_snapshot'     => (float) $product['cost_price'],
                    'selling_price_snapshot'  => (float) $product['selling_price'],
                    'quantity'                => (int) $item['qty'],
                    'line_total'              => round((float) $product['selling_price'] * (int) $item['qty'], 2),
                ];
            }

            $transactionId = Transaction::create([
                'receipt_no'      => $receiptNo,
                'subtotal'        => $cartResult['subtotal'],
                'discount_amount' => $cartResult['discount_amount'],
                'total_amount'    => $cartResult['total_amount'],
                'payment_method'  => $paymentMethod,
                'cash_tendered'   => $paymentMethod === 'cash' ? $cashTendered : null,
                'change_amount'   => $changeAmount,
                'cashier_id'      => $cashierId,
            ], $lineItems);

            // Step 5: Deduct stock
            self::deductStock($cartItems, $transactionId, $receiptNo);

            $pdo->commit();

            return [
                'success'        => true,
                'transaction_id' => $transactionId,
                'receipt_no'     => $receiptNo,
                'change_amount'  => $changeAmount,
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Checkout failed: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Checkout failed. Please retry.'];
        }
    }

    /**
     * Calculate cart totals.
     *
     * @param  array  $cartItems
     * @param  float  $discountValue
     * @param  string $discountType  'fixed' | 'percent'
     * @return array  {subtotal, discount_amount, total_amount}
     */
    public static function calculateCart(array $cartItems, float $discountValue, string $discountType): array
    {
        $subtotal = 0.00;

        foreach ($cartItems as $item) {
            $qty   = max(1, (int) ($item['qty'] ?? 1));
            $price = (float) ($item['selling_price'] ?? 0);
            $subtotal += round($price * $qty, 2);
        }

        if ($discountType === 'percent') {
            $discountValue  = max(0, min(100, $discountValue));
            $discountAmount = round($subtotal * ($discountValue / 100), 2);
        } else {
            $discountAmount = min(max(0, $discountValue), $subtotal);
        }

        $total = max(0, round($subtotal - $discountAmount, 2));

        return [
            'subtotal'        => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'total_amount'    => $total,
        ];
    }

    /**
     * Deduct stock for each cart item and record movements.
     *
     * @param  array  $cartItems
     * @param  int    $transactionId
     * @param  string $receiptNo
     */
    public static function deductStock(array $cartItems, int $transactionId, string $receiptNo = ''): void
    {
        $cashierId = $_SESSION['user_id'] ?? null;

        foreach ($cartItems as $item) {
            $product = Product::findById((int) $item['product_id']);
            if (!$product) continue;

            $qtyBefore = (int) $product['stock_qty'];
            $qtyAfter  = max(0, $qtyBefore - (int) $item['qty']);

            Product::updateStock((int) $item['product_id'], $qtyAfter);
            StockMovement::record([
                'product_id'    => $item['product_id'],
                'movement_type' => 'sale',
                'qty_change'    => -(int) $item['qty'],
                'qty_before'    => $qtyBefore,
                'qty_after'     => $qtyAfter,
                'reason'        => 'Sale',
                'reference'     => $receiptNo ?: ('TXN-' . $transactionId),
                'created_by'    => $cashierId,
            ]);
        }
    }

    /**
     * Build receipt data for a given transaction.
     *
     * @param  int $transactionId
     * @return array
     */
    public static function buildReceiptData(int $transactionId): array
    {
        $transaction = Transaction::findById($transactionId);
        if (!$transaction) return [];

        $user = null;
        if (!empty($transaction['cashier_id'])) {
            $user = User::findById((int) $transaction['cashier_id']);
        }

        return [
            'transaction'    => $transaction,
            'items'          => $transaction['items'] ?? [],
            'cashier_name'   => $user['full_name']   ?? ($transaction['cashier_name'] ?? 'N/A'),
            'store_name'     => $_SESSION['store_name'] ?? APP_NAME,
            'store_address'  => $_SESSION['store_address'] ?? '',
            'printed_at'     => date('Y-m-d H:i:s'),
        ];
    }
}
