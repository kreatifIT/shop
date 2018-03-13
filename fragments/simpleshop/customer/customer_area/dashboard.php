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


$User  = $this->getVar('User');
$title = $this->getVar('title', '###shop.account_data###');
$text  = $this->getVar('text');

?>
<div class="dashboard">
    <div class="row column">
        <h2 class="margin-bottom"><?= $title ?></h2>

        <?= strlen($text) ? $text : '' ?>
    </div>


    <div class="row column margin-top">
        <a href="<?= rex_getUrl(null, null, ['action' => 'logout']) ?>" class="button">###action.logout###</a>
    </div>
</div>

