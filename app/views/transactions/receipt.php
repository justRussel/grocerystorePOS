<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?= htmlspecialchars($transaction['receipt_no'] ?? '') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
    <style>
        body { margin: 0; padding: 10px; background: #fff; }
        @media screen {
            .receipt-container { max-width: 80mm; margin: 0 auto; border: 1px dashed #ccc; padding: 10px; }
        }
    </style>
</head>
<body>
<div class="receipt-print-area">
    <div class="receipt-container">
        <div class="receipt-header">
            <h4><?= htmlspecialchars($storeName ?? APP_NAME) ?></h4>
            <?php if (!empty($storeAddress)): ?>
                <p><?= htmlspecialchars($storeAddress) ?></p>
            <?php endif; ?>
            <p>Receipt #: <strong><?= htmlspecialchars($transaction['receipt_no']) ?></strong></p>
            <p><?= htmlspecialchars($transaction['created_at']) ?></p>
            <p>Cashier: <?= htmlspecialchars($transaction['cashier_name'] ?? 'N/A') ?></p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Price</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name_snapshot']) ?></td>
                        <td style="text-align:right"><?= (int) $item['quantity'] ?></td>
                        <td style="text-align:right"><?= CURRENCY_SYMBOL . number_format($item['selling_price_snapshot'], 2) ?></td>
                        <td style="text-align:right"><?= CURRENCY_SYMBOL . number_format($item['line_total'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="receipt-totals">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td style="text-align:right"><?= CURRENCY_SYMBOL . number_format($transaction['subtotal'], 2) ?></td>
                </tr>
                <?php if ($transaction['discount_amount'] > 0): ?>
                    <tr>
                        <td>Discount</td>
                        <td style="text-align:right">-<?= CURRENCY_SYMBOL . number_format($transaction['discount_amount'], 2) ?></td>
                    </tr>
                <?php endif; ?>
                <tr class="grand-total">
                    <td><strong>TOTAL</strong></td>
                    <td style="text-align:right"><strong><?= CURRENCY_SYMBOL . number_format($transaction['total_amount'], 2) ?></strong></td>
                </tr>
                <?php if (!empty($transaction['cash_tendered'])): ?>
                    <tr>
                        <td>Cash Tendered</td>
                        <td style="text-align:right"><?= CURRENCY_SYMBOL . number_format($transaction['cash_tendered'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>Change</td>
                        <td style="text-align:right"><?= CURRENCY_SYMBOL . number_format($transaction['change_amount'], 2) ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

        <div class="receipt-footer">
            <p>Thank you for shopping!</p>
            <p>Payment: <?= ucfirst(htmlspecialchars($transaction['payment_method'])) ?></p>
        </div>
    </div>
</div>
</body>
</html>
