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

$template = $this->getVar('template');

?>
<div class="customer-area margin-top margin-large-bottom">
    <?= $this->subfragment('simpleshop/customer/customer_area/'. $template); ?>
</div>