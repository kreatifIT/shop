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


use Kreatif\Model;


class Variant extends Model
{
    use \Kreatif\Project\Traits\Variant;

    const TABLE = "rex_shop_product_has_feature";

    public static function getByVariantKey($key)
    {
        list ($product_id, $variant_key) = explode('|', $key);
        return self::query()
            ->where('product_id', $product_id)
            ->where('variant_key', $variant_key)
            ->findOne();
    }

    public function applyProductData()
    {
        $_product = Product::get($this->getValue('product_id'));
        $product  = clone $_product;
        $product->applyVariantData($this->getData());
        $product_data = $product->getData();

        foreach ($product_data as $key => $value) {
            $this->setValue($key, $value);
        }
    }

    public function isOnline($lang_id = null)
    {
        return true;
    }

    public static function be_getYFields()
    {
        $labels  = [];
        $fields  = [];
        $table   = \rex_yform_manager_table::get(self::TABLE);
        $columns = $table->getFields();
        $params  = [
            'this'       => \rex_yform::factory(),
            'form_array' => [],
        ];

        $params['main_table'] = rex_request('table_name', 'string');

        foreach ($columns as $index => $column) {
            $type = $column->getTypeName();
            $name = $column->getName();

            if ($name == 'variant_key' || $type == 'datestamp' || $name == 'product_id' || $type == 'be_user') {
                continue;
            }
            $elements = $column->toArray();
            $notice   = $column->getElement('notice');
            $class    = 'rex_yform_value_' . trim($type);
            $field    = new $class();

            array_unshift($elements, $type);
            $field->loadParams($params, $elements);
            $field->fieldName  = $name;
            $field->fieldIndex = $index;
            $fields[]          = $field;

            if ($type != 'hidden_input' && $name != 'prio') {
                $labels[] = [
                    'label'  => $field->getLabel(),
                    'notice' => $notice,
                ];
            }
        }
        return [$labels, $fields];
    }
}