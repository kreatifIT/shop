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


$Addon     = $this->getVar('Addon');
$orders    = $this->getVar('orders');
$order_ids = $this->getVar('order_ids');

?>
<fieldset>

    <p><?= $Addon->i18n('omest_shipping.orders_info_text'); ?></p>

    <table class="table">
        <tr>
            <th></th>
            <th><?= $Addon->i18n('label.order_no'); ?></th>
            <th><?= $Addon->i18n('label.customer'); ?></th>
            <th><?= $Addon->i18n('label.order_sum'); ?></th>
        </tr>

        <tbody class="table-hover">
        <?php if (count($orders)): ?>
            <?php foreach ($orders as $order):
                $Address = $order->getShippingAddress();
                ?>
                <tr>
                    <td><input type="checkbox" name="orders[]" value="<?= $order->getValue('id') ?>" <?php if (empty($order_ids) || in_array($order->getValue('id'), $order_ids)) echo 'checked="checked"'; ?>/></td>
                    <td><?= $order->getValue('id') ?></td>
                    <td><?= $Address->getName() ?></td>
                    <td><?= $order->getValue('total') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="10" class="text-center" style="padding:30px 0;"><em><?= $Addon->i18n('omest_shipping.no_orders_to_send'); ?></em></td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>


    <!--    <br/>-->
    <!---->
    <!--    <legend>--><? //= $Addon->i18n('url_settings');
    ?><!--</legend>-->
    <!--    <p>--><? //= $Addon->i18n('setup_column_creating_text');
    ?><!--</p>-->
    <!--    <div class="row">-->
    <!--        <div class="col-sm-12">-->
    <!--            <div class="rex-select-style">-->
    <!--                --><? //= \rex_var_linklist::getWidget(1, 'test', '')
    ?>
    <!--            </div>-->
    <!--        </div>-->
    <!--    </div>-->

</fieldset>