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
use Url\Url;


class Product extends Model
{
    use \Kreatif\Project\Traits\Product;

    const TABLE        = 'rex_shop_product';
    const URL_PARAMKEY = 'product_id';

    protected $__tax          = null;
    protected $__feature_data = null;
    protected $__variants     = null;
    protected $__features     = null;

    public static function filterQuery(\rex_yform_manager_query $stmt = null)
    {
        $lang_id = \rex_clang::getCurrentId();

        if (!$stmt) {
            $stmt = self::query();
        }
        $stmt->whereRaw('(price > 0 OR reduced_price > 0)')
            ->where('amount', 0, '>')
            ->where("name_{$lang_id}", '', '!=')
            ->where('status', 1);
        return $stmt;
    }

    public function getUrl($params = [], $lang_id = null, $withoutVkey = true)
    {
        $vkey = $this->getValue('variant_key');

        if (!$withoutVkey && $vkey) {
            $params = array_merge([
                'vkey' => $vkey,
            ], $params);
        }
        return parent::getUrl($params, $lang_id);
    }

    public function getKey()
    {
        return rtrim($this->getId() . '|' . $this->getValue('variant_key'), '|');
    }

    public function getFeatureValue($featureKey)
    {
        $result     = null;
        $feature    = Feature::getFeatureByKey($featureKey);
        $variantKey = trim($this->getValue('variant_key'));

        if ($feature && $feature->isOnline()) {
            $stmt = FeatureValue::query();
            $stmt->alias('m');
            $stmt->resetSelect();
            $stmt->selectRaw('m.*, jt1.id AS variant_id, jt1.variant_key');
            $stmt->joinRaw('inner', Variant::TABLE, 'jt1', 'jt1.product_id = ' . $this->getId());
            $stmt->where('jt1.type', 'NE', '!=');
            $stmt->where('m.status', 1);
            $stmt->where('m.feature_id', $feature->getId());
            $stmt->whereRaw('(
                    jt1.variant_key = m.id    
                    OR jt1.variant_key LIKE CONCAT(m.id, ",%")    
                    OR jt1.variant_key LIKE CONCAT("%,", m.id, ",%")    
                    OR jt1.variant_key LIKE CONCAT("%,", m.id)    
                )');

            if ($variantKey != '') {
                $stmt->where('m.id', explode(',', $variantKey));
            }
            $result = $stmt->findOne();
        }
        return $result;
    }

    public function getFeatures()
    {
        if ($this->__features === null) {
            $feature_val_ids  = trim($this->getValue('features'));
            $this->__features = strlen($feature_val_ids) ? $this->_getFeatures(explode(',', $feature_val_ids)) : [];
        }
        return $this->__features;
    }

    public function getFeatureValuesByFeatureKeys(array $featureKeys, $filterKeys = [], $ignoreType = false)
    {
        $result     = [];
        $filterIds  = [];
        $variantKey = array_filter(explode(',', trim($this->getValue('variant_key'))));

        if (count($variantKey) && count($filterKeys)) {
            foreach ($filterKeys as $filterKey) {
                if ($feature = Feature::getFeatureByKey($filterKey)) {
                    $stmt = FeatureValue::query();
                    $stmt->where('status', 1);
                    $stmt->where('feature_id', $feature->getId());
                    $stmt->where('id', $variantKey);
                    $collection = $stmt->find();
                    $filterIds  = array_merge($filterIds, $collection->getIds());
                }
            }
        }
        // get feature by order
        $stmt = Feature::query();
        $stmt->where('key', $featureKeys);
        $stmt->where('status', 1);
        $features = $stmt->find();

        foreach ($features as $feature) {
            $stmt = FeatureValue::query();
            $stmt->alias('m');
            $stmt->resetSelect();
            $stmt->selectRaw('m.*, jt1.id AS variant_id, jt1.variant_key');
            $stmt->joinRaw('inner', Variant::TABLE, 'jt1', 'jt1.product_id = ' . $this->getId());
            $stmt->where('m.feature_id', $feature->getId());
            $stmt->where('m.status', 1);
            $stmt->where('m.status', 1);
            if (!$ignoreType) {
                $stmt->where('jt1.type', 'NE', '!=');
            }
            $stmt->groupBy('m.id');
            $stmt->orderBy('jt1.prio');
            $stmt->whereRaw('(
                    jt1.variant_key = m.id    
                    OR jt1.variant_key LIKE CONCAT(m.id, ",%")    
                    OR jt1.variant_key LIKE CONCAT("%,", m.id, ",%")    
                    OR jt1.variant_key LIKE CONCAT("%,", m.id)    
                )');
            if (count($filterIds)) {
                $stmt->resetOrderBy();
                $stmt->orderBy('m.prio');
                foreach ($filterIds as $index => $filterId) {
                    $stmt->whereRaw("(
                            jt1.variant_key = :filter_{$index}_1
                            OR jt1.variant_key LIKE :filter_{$index}_2    
                            OR jt1.variant_key LIKE :filter_{$index}_3    
                            OR jt1.variant_key LIKE :filter_{$index}_4    
                        )", [
                        "filter_{$index}_1" => $filterId,
                        "filter_{$index}_2" => "%,{$filterId}",
                        "filter_{$index}_3" => "%,{$filterId},%",
                        "filter_{$index}_4" => "{$filterId},%",
                    ]);
                }
            } else if (count($variantKey)) {
                // order by variant key
                $oStmt    = clone $stmt;
                $orderIds = $oStmt->find();
                $orderIds = $orderIds->getIds();
                $orderIds = array_unique(array_merge(array_intersect($orderIds, $variantKey), $orderIds));
                if (count($orderIds)) {
                    $stmt->resetOrderBy();
                    $stmt->orderByRaw('FIELD(m.id, ' . implode(',', $orderIds) . ')');
                }
            }
            $_collection = $stmt->find();

            if (count($variantKey)) {
                foreach ($_collection as $_item) {
                    if (in_array($_item->getId(), $variantKey)) {
                        $filterIds[] = $_item->getId();
                        break;
                    }
                }
            }
            if (count($_collection)) {
                $result[$feature->getValue('key')] = $_collection;
            }
        }
        return $result;
    }

    public function hasReducedPrice()
    {
        return $this->getValue('reduced_price') > 0;
    }

    public function getPrice($includeTax = true, $use_reduced = true)
    {
        $type     = $this->getValue('type');
        $price    = $this->getValue('price');
        $discount = $this->getValue('discount');
        $Settings = \rex::getConfig('simpleshop.Settings');

        if ($type == 'giftcard') {
            $includeTax = false;
            $extras     = $this->getValue('extras');
            $price      = $extras['price'];
        } else if ($use_reduced) {
            $reduced = $this->getValue('reduced_price');
            $price   = $reduced > 0 ? $reduced : $price;
        }

        $price = \rex_extension::registerPoint(new \rex_extension_point('simpleshop.Product.getPrice', $price, [
            'product'     => $this,
            'includeTax'  => $includeTax,
            'use_reduced' => $use_reduced,
        ]));

        if (is_object($discount)) {
            if ($discount->getValue('discount_value') > 0) {
                $price -= $discount->getValue('discount_value');
            } else if ($discount->getValue('discount_percent') > 0) {
                $price = $price / 100 * (100 - $discount->getValue('discount_percent'));
            }
        }

        if ($includeTax) {
            $tax = Tax::get($this->getValue('tax'))
                ->getValue('tax');

            if ($Settings['brutto_prices']) {
                $this->__tax = ($price / ($tax + 100)) * $tax;
            } else {
                $this->__tax = $price * $tax * 0.01;
                $price       = $price + $this->__tax;
            }
        } else if ($Settings['brutto_prices']) {
            $tax = Tax::get($this->getValue('tax'))
                ->getValue('tax');

            $this->__tax = ($price / ($tax + 100)) * $tax;
            $price       = $price - $this->__tax;
        } else {
            // prevent rounding problems
            $price = round($price, 2);
        }
        return $price;
    }

    public function getTax()
    {
        if ($this->__tax === null) {
            $this->getPrice(true);
        }
        return $this->__tax;
    }

    public function _getFeatures($feature_val_ids)
    {
        $result = [];

        if (count($feature_val_ids)) {
            $feature_ids    = [];
            $feature_values = [];
            // get feature values
            foreach ($feature_val_ids as $feature_id) {
                $_value = $feature_id ? FeatureValue::get($feature_id) : null;

                if ($_value) {
                    $_id                    = $_value->getValue('feature_id');
                    $feature_ids[]          = $_id;
                    $feature_values[$_id][] = $_value;
                }
            }
            // get features
            if ($feature_ids) {
                $stmt = Feature::query();
                $stmt->where('id', $feature_ids);
                $_features = $stmt->find();
            } else {
                $_features = [];
            }

            foreach ($_features as $feature) {
                // assign feature values to the feature
                $feature->values = $feature_values[$feature->getValue('id')];
                //
                $result[$feature->getValue('key')] = $feature;
            }
        }
        return $result;
    }

    public static function getProductVariant($variant_key)
    {
        list ($product_id, $variant_key) = explode('|', $variant_key);

        // otherwise existing products will be overwritten, even if they are not in the package and exists itself
        // (f.e. kellerei-kurtatsch -> Caliz, Arenis & Curtis + Arenis  0,357l = Arenis 0,357l gets price from package variant )
        $Object = clone self::get($product_id);

        if ($Object && strlen($variant_key)) {
            $Object = $Object->getVariant($variant_key);
        }
        return $Object;
    }

    public function getVariant($variantKey = null)
    {
        $product_id = $this->getValue('id');
        $stmt       = Variant::query();
        $stmt->where('product_id', $product_id);
        $stmt->where('type', 'NE', '!=');
        $stmt->orderBy('prio');

        if ($variantKey) {
            $stmt->where('variant_key', $variantKey);
        }
        $variant = $stmt->findOne();

        if (!$variant) {
            throw new ProductException("The variant doesn't exist --key:{$product_id}|{$variantKey}", 3);
        }
        // apply default values from variant
        $this->applyVariantData($variant->getData());
        return $this;
    }

    public function getFeatureVariants()
    {
        if ($this->__feature_data === null) {
            $sql  = \rex_sql::factory();
            $stmt = Variant::query()
                ->resetSelect()
                ->select('id')
                ->select('variant_key')
                ->where('product_id', $this->getId())
                ->where('type', 'NE', '!=')
                ->orderBy('prio', 'asc');

            $_variants = $stmt->find();

            $this->__feature_data = [
                'variants' => [],
            ];

            foreach ($_variants as $variant) {
                $key  = $variant->getValue('variant_key');
                $_ids = array_filter(explode(',', $key));

                // order ids by feature priority
                $query = '
                    SELECT m.id 
                    FROM ' . FeatureValue::TABLE . ' AS m
                    LEFT JOIN ' . Feature::TABLE . ' AS jt1 ON jt1.id = m.feature_id
                    WHERE m.id IN(' . implode(',', $_ids) . ')
                    ORDER BY jt1.prio ASC
                ';
                $_ids  = $sql->getArray($query, [], \PDO::FETCH_COLUMN);

                if (count($_ids)) {
                    // apply default values from product
                    $clone   = clone $this;
                    $variant = $clone->getVariant($key);
                    // set variants
                    $this->__feature_data['variants'][implode(',', $_ids)] = $variant;
                }
            }
        }
        return $this->__feature_data;
    }

    public static function getProductByKey($key, $cart_quantity = null, $extras = [], $throwErrors = true)
    {
        if (!strlen($key)) {
            return false;
        }
        list ($product_id, $variant_key) = explode('|', trim($key, '|'));

        $_this = self::query()
            ->where('id', $product_id)
            ->where('status', 1)
            ->findOne();

        if (!$_this) {
            throw new ProductException("No product with ID = " . $product_id . " exists --key:{$key}", 1);
        }
        $features = [];
        $_this->setValue('key', $key);
        $_this->setValue('cart_quantity', $cart_quantity);
        $_this->setValue('extras', $extras);
        $_features   = $_this->getValue('features');
        $feature_ids = $variant_key ? explode(',', $variant_key) : [];

        if (count($feature_ids)) {
            $variant = Variant::query()
                ->where('product_id', $product_id)
                ->where('type', 'NE', '!=')
                ->where('variant_key', $variant_key)
                ->findOne();

            if (!$variant) {
                // the given combination does not exist!
                throw new ProductException("The variant doesn't exist --key:{$key}", 3);
            }
            $_this->applyVariantData($variant->getData());
        }

        \rex_extension::registerPoint(new \rex_extension_point('simpleshop.Product.getProductByKey', $_this, [
            'cart_quantity' => $cart_quantity,
            'extras'        => $extras,
            'throwErrors'   => $throwErrors,
        ]));

        if ($_features && count($feature_ids)) {
            $feature_ids = \rex_extension::registerPoint(new \rex_extension_point('simpleshop.Product.getProductByKey.featureIds', $feature_ids));

            foreach ($feature_ids as $feature_id) {
                // get variants
                $stmt = FeatureValue::query();
                $stmt->alias('m');
                $stmt->select('jt1.prio', 'feature_prio');
                $stmt->join(Feature::TABLE, 'jt1', 'jt1.id', 'm.feature_id');
                $stmt->where('m.id', $feature_id);
                $stmt->where('m.status', 1);
                $stmt->orderBy('jt1.prio', 'desc');
                $feature = $stmt->findOne();


                if ($throwErrors) {
                    if (!$feature) {
                        throw new ProductException("No feature with ID = " . $feature_id . " exists --key:{$key}", 2);
                    } else if ($_this->getValue('amount') <= 0) {
                        // make some availabilty checks
                        throw new ProductException("Product not available any more --key:{$key}", 4);
                    } else if ($_this->getValue('amount') < $_this->getValue('cart_quantity')) {
                        throw new ProductException("Amount of product is lower than cart quantity --key:{$key}", 5);
                    }
                }
                $__features[$feature->getValue('feature_prio')] = $feature;
            }
            ksort($__features);
            $features = [];
            foreach ($__features as $_feature) {
                $features[] = $_feature;
            }
        } else {
            if ($throwErrors) {
                if ($_this->getValue('amount') <= 0) {
                    // make some availabilty checks
                    throw new ProductException("Product not available any more --key:{$key}", 4);
                } else if ($_this->getValue('amount') < $_this->getValue('cart_quantity')) {
                    throw new ProductException("Amount of product is lower than cart quantity --key:{$key}", 5);
                }
            }
        }
        $_this->features = $features;
        return $_this;
    }

    public function applyVariantData($variant_data)
    {
        foreach ($variant_data as $key => $value) {
            if (in_array($key, ['price', 'reduced_price', 'width', 'height', 'weight', 'length'])) {
                if ((float)$value > 0) {
                    $this->setValue($key, (float)$value);
                }
            } else if ($key == 'prio') {
                $this->setValue('variant_prio', $value);
            } else if ($key == 'id') {
                $this->setValue('variant_id', $value);
            } else if ($key == 'amount') {
                if ($value != '') {
                    $this->setValue($key, $value);
                }
            } else if ($value != '' && !in_array($key, ['id', 'product_id', 'type'])) {
                $this->setValue($key, $value);
            }
        }
        if ($variant_data['type'] == 'FREE') {
            // if variant is free set price to zero
            $this->setValue('price', 0);
            $this->setValue('reduced_price', 0);
        } else if ((float)$variant_data['price'] > 0 && (float)$variant_data['reduced_price'] <= 0) {
            // if variant has a price
            $this->setValue('reduced_price', 0);
        }
    }

    public function getDiscountPercentage()
    {
        $result = 0;

        if ($this->hasReducedPrice()) {
            $Z      = $this->getValue('price') - $this->getValue('reduced_price');
            $result = $Z * 100 / $this->getValue('price');
        }
        return $result;
    }

    public static function ext_yform_data_delete(\rex_extension_point $Ep)
    {
        $result = $Ep->getSubject();

        if ($result !== false && $Ep->getParam('table')
                ->getTableName() == self::TABLE
        ) {
            // remove all related variants
            $obj_id = $Ep->getParam('data_id');
            $query  = "DELETE FROM " . Variant::TABLE . " WHERE product_id = {$obj_id}";
            $sql    = \rex_sql::factory();
            $sql->setQuery($query);
        }
        return $result;
    }

    public static function ext_setUrlObject(\rex_extension_point $Ep)
    {
        $Object = $Ep->getSubject();

        if ($Object && $Object->getTableName() == self::TABLE) {
            $vkey = trim(rex_get('vkey', 'string'));

            if ($vkey != '') {
                try {
                    $Object = $Object->getVariant($vkey);

                    \rex_extension::register('YREWRITE_ROBOTS_TAG', function (\rex_extension_point $Ep) {
                        return false;
                    });
                } catch (ProductException $ex) {
                    \rex_response::setStatus(404);
                    $Object = false;
                }
            } else if (!$Object->valueIsset('code')) {
                $collection = $Object->getFeatureVariants();
                $Variant    = array_shift($collection['variants']);

                if ($Variant) {
                    $Object                 = $Variant;
                    $Object->__feature_data = null;
                } else {
                    \rex_response::setStatus(404);
                    $Object = false;
                }
            }
        }
        return $Object;
    }

    public static function ext_queryCollection(\rex_extension_point $Ep)
    {
        $collection = $Ep->getSubject();

        if ($Ep->getParam('table') == self::TABLE) {
            $result = [];
            $sql    = \rex_sql::factory();

            foreach ($collection as $Object) {
                $sql->setQuery("
                    SELECT variant_key 
                    FROM " . Variant::TABLE . " 
                    WHERE product_id = :pid 
                    AND type != 'NE'
                    ORDER BY prio ASC LIMIT 1", ['pid' => $Object->getId()]);

                if ($sql->hasNext() && $vkey = $sql->getValue('variant_key')) {
                    $Object = $Object->getVariant($vkey);
                }
                $result[] = $Object;
            }
            $collection = $result;
        }
        return $collection;
    }

    public static function ext_tableManagerInfo(\rex_extension_point $Ep)
    {
        $subject = $Ep->getSubject();
        $Manager = $Ep->getParam('manager');

        if ($Manager && $Manager->table->getTablename() == Product::TABLE) {
            $fragment = new \rex_fragment();
            $content  = $fragment->parse('simpleshop/backend/product_list_functions.php');

            if (strlen($content)) {
                $fragment = new \rex_fragment();
                $fragment->setVar('body', $content, false);
                $fragment->setVar('class', 'action');
                $subject = $fragment->parse('core/page/section.php');
            }
        }
        return $subject;
    }


    public static function be_toggleRexCategoryId()
    {
        $request = \rex_api_simpleshop_be_api::$inst->request;
        $Object  = self::get($request['id']);

        if ($Object) {
            $categories = $Object->getArrayValue('category');

            if ($request['action'] == 'add') {
                $categories[] = $request['cat_id'];
            } else {
                $index = array_search($request['cat_id'], $categories);

                if ($index !== false) {
                    unset($categories[$index]);
                }
            }
            $categories = array_unique($categories);

            $Object->setValue('category', implode(',', $categories));

            if (!$Object->save()) {
                \rex_api_simpleshop_be_api::$inst->success = false;
                \rex_api_simpleshop_be_api::$inst->errors  = array_merge(\rex_api_simpleshop_be_api::$inst->errors, $Object->getMessages());
            }
            \rex_api_simpleshop_be_api::$inst->response['categories'] = $categories;
        }
    }
}

class ProductException extends \Exception
{
    /*
     * Error Codes:
     * 1 = No product with ID
     * 2 = No feature with ID
     * 3 = The variant doesn't exist
     * 4 = Product not available any more
     * 5 = Amount of product is lower than cart quantity
     */
}