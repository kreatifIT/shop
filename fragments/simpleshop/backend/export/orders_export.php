<?php

/**
 * This file is part of the Simpleshop package.
 *
 * @author FriendsOfREDAXO
 * @author a.platter@kreatif.it
 * @author jan.kristinus@yakamara.de
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FriendsOfREDAXO\Simpleshop;

$buffer    = $this->getVar('buffer');
$output    = $this->getVar('output');
$order_ids = $this->getVar('order_ids');

$tax_vals = [];
$taxes    = Tax::query()->where('status', 1)->orderBy('tax')->find();
$orders   = Order::query()->where('id', $order_ids)->orderBy('createdate')->find();
$csv_head = ['Rechn.-Nr', 'ID', 'Jahr', 'Monat', 'Datum', 'Währung', 'Kunde', 'Land', 'Shop', 'Zahlart', 'Grundlage', 'Rabatt', 'Summe'];
$csv_body = [];
$totals   = ['base' => 0, 'discount' => 0, 'sum' => 0];

foreach ($taxes as $tax)
{
    $_id            = $tax->getValue('id');
    $tax_vals[$_id] = $tax->getValue('tax');
    $csv_head[]     = "Mwst.-Satz {$tax->getValue('tax')}%";
}

foreach ($orders as $order)
{
    $_taxes      = [];
    $date        = $order->getValue('createdate');
    $order_ts    = strtotime($date);
    $order_id    = $order->getValue('id');
    $Payment     = $order->getValue('payment');
    $Shipping    = $order->getValue('shipping');
    $Address     = $order->getInvoiceAddress();
    $products    = OrderProduct::query()->where('order_id', $order_id)->find();
    $sh_tax      = $Shipping ? $Shipping->getValue('tax') : 0;
    $subtotal    = $order->getValue('subtotal');
    $discount    = $order->getValue('discount') >= $subtotal ? $subtotal : ($order->getValue('discount') ?: '');
    $base_price  = $order->getValue('subtotal') - $order->getValue('tax') + ($Shipping ? $Shipping->getValue('price') : 0);
    $total_price = $order->getValue('total');

    $data = [
        '',
        $order_id,
        date('Y', $order_ts),
        date('m', $order_ts),
        $date,
        '€',
        $Address->getName(),
        $Address->getValue('country'),
        \rex::getServerName(),
        $Payment ? $Payment->getName() : '',
        format_price($base_price),
        $discount,
        format_price($total_price),
    ];

    $totals['base'] += $base_price;
    $totals['discount'] += $discount;
    $totals['sum'] += $total_price;

    if ($Shipping && $sh_tax)

    {
        $sh_tax_p = $Shipping->getValue('tax_percentage');
        $tax_key  = array_search($sh_tax_p, $tax_vals);

        if ($tax_key === FALSE)
        {
            $tax_key            = '_' . $sh_tax_p;
            $tax_vals[$tax_key] = $sh_tax_p;
            $csv_head[]         = "Mwst.-Satz {$sh_tax_p}%";
        }
        $_taxes[$tax_key] += $Shipping->getValue('tax');
    }
    foreach ($products as $product)
    {
        $_data    = $product->getValue('data');
        $quantity = $product->getValue('quantity');
        $tax_id   = $_data->getValue('tax');
        $_taxes[$tax_id] += $_data->getValue('price') / 100 * ($tax_vals[$tax_id]) * $quantity;
    }
    foreach ($tax_vals as $tax_id => $tax)
    {
        $data[] = $_taxes[$tax_id] ? format_price($_taxes[$tax_id]) : '';
        $totals['tax_'. $tax_id] += $_taxes[$tax_id];
    }
    $csv_body[] = $data;
}
$csv_body[] = array_merge(['','','','','','','','','',''], $totals);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// OUTPUT
if ($output == 'html')
{
    $html = [
        '<table border="1" cellpadding="5" cellspacing="0">',
        '<tr><th>' . implode('</th><th>', $csv_head) . '</th></tr>',
    ];
    foreach ($csv_body as $item)
    {
        $html[] = '<tr><td>' . implode('</td><td>', $item) . '</td></tr>';
    }
    $html[] = '</table>';
    echo implode('', $html);
}
else
{
    $buffer = fopen('php://output', 'w');
    // set headline
    fputcsv($buffer, $csv_head, ';');
    foreach ($csv_body as $item)
    {
        fputcsv($buffer, $item, ';');
    }
    fclose($buffer);
}
