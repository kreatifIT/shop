<?php

/**
 * This file is part of the Shop package.
 *
 * @author Kreatif GmbH
 * @author a.platter@kreatif.it
 * Date: 12.10.16
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FriendsOfREDAXO\Simpleshop;


\rex_extension::register('YFORM_DATA_DELETE', ['\FriendsOfREDAXO\Simpleshop\Category', 'ext_yform_data_delete']);
\rex_extension::register('YFORM_DATA_DELETE', ['\FriendsOfREDAXO\Simpleshop\Feature', 'ext_yform_data_delete']);
\rex_extension::register('YFORM_DATA_DELETE', ['\FriendsOfREDAXO\Simpleshop\FeatureValue', 'ext_yform_data_delete']);
\rex_extension::register('YFORM_DATA_DELETE', ['\FriendsOfREDAXO\Simpleshop\Product', 'ext_yform_data_delete']);
\rex_extension::register('YFORM_DATA_DELETE', ['\FriendsOfREDAXO\Simpleshop\Tax', 'ext_yform_data_delete']);
\rex_extension::register('YFORM_DATA_DELETE', ['\FriendsOfREDAXO\Simpleshop\Order', 'ext_yform_data_delete']);
\rex_extension::register('YFORM_DATASET_FORM_SETVALUEFIELD', ['\FriendsOfREDAXO\Simpleshop\Model', 'ext_setValueField']);
\rex_extension::register('YFORM_DATASET_FORM_SETVALIDATEFIELD', ['\FriendsOfREDAXO\Simpleshop\Model', 'ext_setValidateField']);
\rex_extension::register('kreatif.Model.queryCollection', ['\FriendsOfREDAXO\Simpleshop\Product', 'ext_queryCollection']);
\rex_extension::register('project.layoutBottom', ['\FriendsOfREDAXO\Simpleshop\CartController', 'ext_project_layoutBottom']);
\rex_extension::register('project.setUrlObject', ['\FriendsOfREDAXO\Simpleshop\Product', 'ext_setUrlObject']);
\rex_extension::register('YFORM_MANAGER_REX_INFO', ['\FriendsOfREDAXO\Simpleshop\Product', 'ext_tableManagerInfo']);


\rex_extension::register('PACKAGES_INCLUDED', function (\rex_extension_point $Ep) {
    \rex_login::startSession();


    if (rex_get('action', 'string') == 'logout') {
        Customer::logout();
    }
    if (\rex::isBackend() && \rex::getUser()) {
        \rex_view::setJsProperty('simpleshop', [
            'ajax_url' => \rex_url::frontendController(),
        ]);
    }
    if (\rex_addon::get('kreatif-mpdf')->isAvailable()) {
        \Kreatif\Mpdf\Mpdf::addCSSPath($this->getPath('assets/scss/pdf_styles.scss'));
    }
    return $Ep->getSubject();
});

\rex_extension::register('simpleshop.Order.applyDiscounts', ['\FriendsOfREDAXO\Simpleshop\DiscountGroup', 'ext_applyDiscounts']);

\rex_extension::register('yform/usability.getStatusColumnParams.options', function (\rex_extension_point $Ep) {
    $options = $Ep->getSubject();
    $table   = $Ep->getParam('table');

    if ($table == Order::TABLE) {
        $list = $Ep->getParam('list');

        if ($list && $list->getValue('ref_order_id')) {
            $options = ['CN' => $options['CN']];
        } else if ($list && count(Order::query()
            ->where('ref_order_id', $list->getValue('id'))
            ->find())) {
            $options = ['CA' => $options['CA']];
        } else {
            unset($options['CN']);
        }
    }

    return $options;
});

\rex_extension::register('yform/usability.addDragNDropSort.filters', function ($params) {
    $subject = $params->getSubject();
    $params  = $params->getParam('list_params');

    if (is_object($params['params']['table']) && $params['params']['table']->getTableName() == Category::TABLE) {
        $_filter = rex_get('rex_yform_filter', 'array');

        if (isset($_filter['parent_id'])) {
            $subject[] = 'parent_id=' . (int)$_filter['parent_id'];
        } else {
            $subject[] = 'parent_id=0';
        }
    }
    return $subject;
});

\rex_view::setJsProperty('simpleshop', [
    'ajax_url'   => \rex_url::frontendController(),
]);