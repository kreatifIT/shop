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

$_FUNC = rex_post('func', 'string');

if($_FUNC == 'save')
{
    unset($_POST['func']);
    \rex::setConfig('simpleshop.Settings', $_POST);
}

$sections = '';
$fragment = new \rex_fragment();
$fragment->setVar('Addon', $this);
$fragment->setVar('Settings', \rex::getConfig('simpleshop.Settings'));
$content  = $fragment->parse('simpleshop/backend/settings.php');

$fragment = new \rex_fragment();
$fragment->setVar('body', $content, FALSE);
$fragment->setVar('class', 'edit', FALSE);
$fragment->setVar('title', $this->i18n('setup'), FALSE);
$sections .= $fragment->parse('core/page/section.php');

$formElements = [
    ['field' => '<a class="btn btn-abort" href="' . \rex_url::currentBackendPage() . '">' . \rex_i18n::msg('form_abort') . '</a>'],
    ['field' => '<button class="btn btn-apply rex-form-aligned" type="submit" name="func" value="save"' . \rex::getAccesskey(\rex_i18n::msg('update'), 'apply') . '>' . \rex_i18n::msg('update') . '</button>'],
];
$fragment = new \rex_fragment();
$fragment->setVar('elements', $formElements, FALSE);
$buttons = $fragment->parse('core/form/submit.php');

$fragment = new \rex_fragment();
$fragment->setVar('class', 'edit', FALSE);
$fragment->setVar('buttons', $buttons, FALSE);
$sections .= $fragment->parse('core/page/section.php');

echo '<form action="' . \rex_url::currentBackendPage() . '" method="post">' . $sections . '</form>';