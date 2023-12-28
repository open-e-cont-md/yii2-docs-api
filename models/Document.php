<?php

namespace app\models;

use Yii;
use common\models\Macros;

/**
 * This is the model class for table "ut_content".
 *
 * @property integer $contentID
 * @property string $ParentID
 * @property string $alias
 * @property string $Header
 * @property string $Body_ru
 * @property string $Body_ro
 * @property string $Body_en
 * @property string $ImageURL
 * @property string $Title
 * @property string $Keywords
 * @property string $Descr
 * @property integer $isPublic
 * @property integer $OrderIndex
 * @property integer $noIndex
 * @property integer $noFollow
 * @property string $MenuHeader
 * @property string $public_date
 * @property string $last_update
 * @property string $sitemap_frequency
 */
class Document extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_document';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['alias', 'Header', 'Body_ru', 'Body_ro', 'Body_en', 'Title', 'Keywords', 'Descr', 'MenuHeader', 'public_date', 'last_update', 'sitemap_frequency'], 'string'],
            [['isPublic', 'OrderIndex', 'noIndex', 'noFollow'], 'integer'],
            [['ParentID', 'ImageURL'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'contentID' => 'Content ID',
            'ParentID' => 'Parent ID',
            'alias' => 'Alias',
            'Header' => 'Header',
            'Body_ru' => 'Body Ru',
            'Body_ro' => 'Body Ro',
            'Body_en' => 'Body En',
            'ImageURL' => 'Image Url',
            'Title' => 'Title',
            'Keywords' => 'Keywords',
            'Descr' => 'Descr',
            'isPublic' => 'Is Public',
            'OrderIndex' => 'Order Index',
            'noIndex' => 'No Index',
            'noFollow' => 'No Follow',
            'MenuHeader' => 'Menu Header',
            'public_date' => 'Public Date',
            'last_update' => 'Last Update',
            'sitemap_frequency' => 'Sitemap Frequency'
        ];
    }

    public function CheckIP($range_array, $check_ip)
    {
        $ip_long = ip2long ($check_ip);
        foreach ($range_array as $i => $v)
        {
            $ip_arr = explode ('/' , $v);
            $network_long = ip2long ($ip_arr[0]);
            if (isset($ip_arr[1])) {
                $x = ip2long ($ip_arr[1]);
                $mask = long2ip ($x) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1]);
            }
            else
                $mask = 0xffffffff;
                // echo ">".$ip_arr[1]."> ".decbin($mask)."\n";
                if(( $ip_long & $mask ) == ( $network_long & $mask )) return true;
        }
        return false;
    }

    public function getStructure()
    {
        return $this->hasOne(SysStructure::class, ['StructureID' => 'ParentID']);
    }

    public static function groupFooter()
    {
        $query = "SELECT alias, Header FROM ut_footer_header WHERE (isPublic = 1) AND (menu_alias = 'footer') ORDER BY OrderIndex";
        $ret = Yii::$app->db->createCommand($query)->queryAll();
        return $ret;
    }
    public static function menuByGroup()
    {
        $query = "SELECT alias, MenuHeader, footer FROM ut_content WHERE (isPublic = 1) AND (footer != '') ORDER BY OrderIndex";
        $ret = Yii::$app->db->createCommand($query)->queryAll();
        return $ret;
    }

    public static function menuByBranch($alias)
    {
        $query = "SELECT Header, alias FROM ut_footer_header WHERE (isPublic = 1) AND (menu_alias = '{$alias}') ORDER BY OrderIndex";
        $res = Yii::$app->db->createCommand($query)->queryAll();    //  \PDO::FETCH_CLASS
        return $res;
    }

    public static function aliasesByAlias($alias = 'home', $lang = 'ro')
    {
        $alias = ($alias != '') ? $alias : 'home';
        $query = "SELECT alias FROM ut_content WHERE (json_get(alias, '{$lang}') = '{$alias}')";
        $res = Yii::$app->db->createCommand($query)->queryOne();    //  \PDO::FETCH_CLASS

        if ($res) return $res; else return ['alias'=>['ro'=> '','ru'=> '','en'=> '']];
    }

    public static function menuByParent($parents = [], $menu_show = 0)
    {
        $in = "'xxx'"; foreach ($parents as $v) $in .= ",'$v'";
        $query = "SELECT alias, MenuHeader, noFollow, noIndex, ParentID FROM ut_document
            WHERE (isPublic = 1) ".($menu_show > 0 ? 'AND (menu_show = 1)' : '')." AND (ParentID IN ($in))
            ORDER BY OrderIndex";
//var_dump($query);
        $res = Yii::$app->db->createCommand($query)->queryAll();    //  \PDO::FETCH_CLASS
        return $res;
    }

    public static function structureByParent($parents = [], $menu_show = 0, $current_alias = 'home')
    {
        $in = "'xxx'"; foreach ($parents as $v) $in .= ",'$v'";
        $current_alias = ($current_alias != '') ? $current_alias : 'home';
/*
        $query = "SELECT alias, MenuHeader, noFollow, noIndex, footer FROM ut_content
            WHERE (isPublic = 1) ".($menu_show > 0 ? 'AND (menu_show = 1)' : '')." AND (ParentID IN ($in))
            ORDER BY OrderIndex";
        $res[0] = Yii::$app->db->createCommand($query)->queryAll();    //  \PDO::FETCH_CLASS

        $query2 = "SELECT * FROM sys_structure WHERE (ParentID IN ($in)) ORDER BY TreeCode, OrderIndex";
        $res[1] = Yii::$app->db->createCommand($query2)->queryAll();    //  \PDO::FETCH_CLASS
*/
        $query = "SELECT documentID AS ID, alias, MenuHeader, noFollow, noIndex, OrderIndex, NULL AS StructureID, ParentID, NULL AS submenu, '' AS icon
        FROM ut_document WHERE (isPublic = 1) ".($menu_show > 0 ? 'AND (menu_show = 1)' : '')." AND (ParentID IN ($in))
        UNION ALL
        SELECT 0 AS ID, NULL AS alias, FolderName AS MenuHeader, 1 AS noFollow, 1 AS noIndex, OrderIndex, StructureID, ParentID, NULL AS submenu, icon
        FROM sys_structure WHERE (ParentID IN ($in))
        ORDER BY OrderIndex";
//var_dump($query);exit;
        $res = Yii::$app->db->createCommand($query)->queryAll();    //  \PDO::FETCH_CLASS

        foreach ($res as $k => $v) {
            $id = ($v['ID'] != 0) ? $v['ID'] : $v['StructureID'];
            $alias = ($v['alias']) ? json_decode($v['alias'])->{Yii::$app->language} : '';
            $res[$k]['active'] = $current_alias === $alias;

            if ($v['StructureID'] != '') { //var_dump('k: '.$k);
                $query2 = "SELECT documentID AS ID, alias, MenuHeader, noFollow, noIndex, OrderIndex, NULL AS StructureID, ParentID, NULL AS submenu, '' AS icon
        FROM ut_document WHERE (isPublic = 1) ".($menu_show > 0 ? 'AND (menu_show = 1)' : '')." AND (ParentID IN ('{$v['StructureID']}'))
        UNION ALL
        SELECT 0 AS ID, NULL AS alias, FolderName AS MenuHeader, 1 AS noFollow, 1 AS noIndex, OrderIndex, StructureID, ParentID, NULL AS submenu, icon
        FROM sys_structure WHERE (ParentID IN ('{$v['StructureID']}'))
        ORDER BY OrderIndex";
//var_dump($query2);exit;
                $res2 = Yii::$app->db->createCommand($query2)->queryAll();    //  \PDO::FETCH_CLASS
//                $res[$k]['query'] = 'Q2: '.$query2;
                $res[$k]['submenu'] = $res2;

                foreach ($res2 as $k2 => $v2) {
                    $id2 = ($v2['ID'] != 0) ? $v2['ID'] : $v2['StructureID'];
                    $alias2 = ($v2['alias']) ? json_decode($v2['alias'])->{Yii::$app->language} : '';
//                    $res[$k]['submenu'][$k2]['active'] = $current_alias === (($alias2 != 'home') ? $alias2 : '');
                    $res[$k]['submenu'][$k2]['active'] = false;
                    if ( $current_alias === (($alias2 != 'home') ? $alias2 : '') )
                    {
                        $res[$k]['submenu'][$k2]['active'] = true;
                        //$res[$k]['active'] = true;
                    }

                    if ($v2['StructureID'] != '') { //var_dump('k2: '.$k2);
                        $query3 = "SELECT documentID AS ID, alias, MenuHeader, noFollow, noIndex, OrderIndex, NULL AS StructureID, ParentID, NULL AS submenu, '' AS icon, Body_ru AS Body
        FROM ut_document WHERE (isPublic = 1) ".($menu_show > 0 ? 'AND (menu_show = 1)' : '')." AND (ParentID IN ('{$v2['StructureID']}'))
        UNION ALL
        SELECT 0 AS ID, NULL AS alias, FolderName AS MenuHeader, 1 AS noFollow, 1 AS noIndex, OrderIndex, StructureID, ParentID, NULL AS submenu, '' AS icon, '' AS Body
        FROM sys_structure WHERE (ParentID IN ('{$v2['StructureID']}'))
        ORDER BY OrderIndex";
//var_dump($query3);exit;
                        $res3 = Yii::$app->db->createCommand($query3)->queryAll();    //  \PDO::FETCH_CLASS
//                        $res[$k][$k2]['query'] = 'Q3: '.$query3;
                        $res[$k]['submenu'][$k2]['submenu'] = $res3;

                        foreach ($res3 as $k3 => $v3) {
                            $id3 = ($v3['ID'] != 0) ? $v3['ID'] : $v3['StructureID'];
                            $alias3 = ($v3['alias']) ? json_decode($v3['alias'])->{Yii::$app->language} : '';
//                            $res[$k]['submenu'][$k2]['submenu'][$k3]['active'] = $current_alias === (($alias3 != 'home') ? $alias3 : '');
                            $res[$k]['submenu'][$k2]['submenu'][$k3]['active'] = false;
                            if ($current_alias === (($alias3 != 'home') ? $alias3 : ''))
                            {
                                $res[$k]['submenu'][$k2]['submenu'][$k3]['active'] = true;
                                $res[$k]['submenu'][$k2]['active'] = true;
                                $res[$k]['active'] = true;
                                $res[$k]['submenu'][0]['active'] = true;
                            }
                        }


                    }
                }
            }
            //else                $res['submenu'] = null;
        }


//var_dump($query);
        return $res;
    }


    public static function structureByID($parents = [], $menu_show = 0, $current_alias = 'home', $prefix = '')
    {
        $in = "'xxx'"; foreach ($parents as $v) $in .= ",'$v'";
        $current_alias = ($current_alias != '') ? $current_alias : 'home';

//  LEVEL  1
        $query = "/*SELECT documentID AS ID, alias, MenuHeader, noFollow, noIndex, OrderIndex, NULL AS StructureID, ParentID, NULL AS submenu, '' AS icon, 0 AS sys_node_only,
        Header, Body_".Yii::$app->language." AS Body, '' AS sys_alias
        FROM ut_document WHERE (isPublic = 1) ".($menu_show > 0 ? 'AND (menu_show = 1)' : '')." AND (ParentID IN ($in))
        UNION ALL*/
        SELECT 0 AS ID, NULL AS alias, sys_menu_header AS MenuHeader, 1 AS noFollow, 1 AS noIndex, OrderIndex, StructureID, ParentID, NULL AS submenu, icon, sys_node_only,
        '' AS Header, '' AS Body, sys_alias, is_active
        FROM sys_structure WHERE (ParentID IN ($in)) AND (is_active = 1)
        ORDER BY OrderIndex";
//var_dump($query);exit;
        $res = Yii::$app->db->createCommand($query)->queryAll();    //  \PDO::FETCH_CLASS

        foreach ($res as $k => $v) {
            $id = ($v['ID'] != 0) ? $v['ID'] : $v['StructureID'];
            //$alias = ($v['alias']) ? json_decode($v['alias'])->{Yii::$app->language} : '';
            $alias = ($v['sys_alias']) ? $v['sys_alias'] : '';
            $res[$k]['active'] = $current_alias === $alias;

//  LEVEL  2
            if ($v['StructureID'] != '') { //var_dump('k: '.$k);
//                if ($v['sys_node_only'] == 1)
//                    $query2 = "SELECT 0 AS ID, NULL AS alias, sys_menu_header AS MenuHeader, 1 AS noFollow, 1 AS noIndex, OrderIndex, StructureID, ParentID, NULL AS submenu, icon, sys_node_only, sys_alias
//        FROM sys_structure WHERE (ParentID IN ('{$v['StructureID']}'))
//        ORDER BY OrderIndex";
//                else
                $query2 = "SELECT documentID AS ID, alias, MenuHeader, noFollow, noIndex, OrderIndex, NULL AS StructureID, ParentID, NULL AS submenu, '' AS icon, 0 AS sys_node_only,
        Header, Body_".Yii::$app->language." AS Body, '' AS sys_alias, 0 AS is_active
        FROM ut_document WHERE (isPublic = 1) ".($menu_show > 0 ? 'AND (menu_show = 1)' : '')." AND (ParentID IN ('{$v['StructureID']}'))
";
                if ($prefix != '') $query2 .= " AND (json_get(alias, 'ru') LIKE '$prefix%') AND (OrderIndex = 0)";
                else $query2 .= " AND (OrderIndex > 0)";
                $query2 .= " UNION ALL
        SELECT 0 AS ID, NULL AS alias, sys_menu_header AS MenuHeader, 1 AS noFollow, 1 AS noIndex, OrderIndex, StructureID, ParentID, NULL AS submenu, icon, sys_node_only,
        '' AS Header, '' AS Body, sys_alias, is_active
        FROM sys_structure WHERE (ParentID IN ('{$v['StructureID']}')) AND (is_active = 1)
        ORDER BY OrderIndex";
//var_dump($query2);exit;
                $res2 = Yii::$app->db->createCommand($query2)->queryAll();    //  \PDO::FETCH_CLASS
                $res[$k]['submenu'] = $res2;

                foreach ($res2 as $k2 => $v2) {
                    $id2 = ($v2['ID'] != 0) ? $v2['ID'] : $v2['StructureID'];

                    if ($v2['sys_node_only'] == 1)
                        $alias2 = ($v2['sys_alias']) ? $v2['sys_alias'] : '';
                    else
                        $alias2 = ($v2['alias']) ? json_decode($v2['alias'])->{Yii::$app->language} : '';
//var_dump($v2['sys_node_only'], $alias2, $v2['sys_alias']);
                    $res[$k]['submenu'][$k2]['active'] = false;
                    if ( $current_alias === (($alias2 != 'home') ? $alias2 : '') )
                    {
                        $res[$k]['submenu'][$k2]['active'] = true;
                        $res[$k]['active'] = true;
                    }




//  LEVEL  3
                    if ($v2['StructureID'] != '') { //var_dump('k2: '.$k2);
                        $query3 = "SELECT documentID AS ID, alias, MenuHeader, noFollow, noIndex, OrderIndex, NULL AS StructureID, ParentID, NULL AS submenu, '' AS icon, 0 AS sys_node_only,
        Header, Body_".Yii::$app->language." AS Body, '' AS sys_alias, 0 AS is_active
        FROM ut_document WHERE (isPublic = 1) ".($menu_show > 0 ? 'AND (menu_show = 1)' : '')." AND (ParentID IN ('{$v2['StructureID']}'))
        AND (OrderIndex > 0)
        UNION ALL
        SELECT 0 AS ID, NULL AS alias, sys_menu_header AS MenuHeader, 1 AS noFollow, 1 AS noIndex, OrderIndex, StructureID, ParentID, NULL AS submenu, '' AS icon, sys_node_only,
        '' AS Header, '' AS Body, sys_alias, is_active
        FROM sys_structure WHERE (ParentID IN ('{$v2['StructureID']}')) AND (is_active = 1)
        ORDER BY OrderIndex";
//var_dump($query3);exit;
                        $res3 = Yii::$app->db->createCommand($query3)->queryAll();    //  \PDO::FETCH_CLASS
                        $res[$k]['submenu'][$k2]['submenu'] = $res3;

                        foreach ($res3 as $k3 => $v3) {
                            $id3 = ($v3['ID'] != 0) ? $v3['ID'] : $v3['StructureID'];

                            //$alias3 = ($v3['alias']) ? json_decode($v3['alias'])->{Yii::$app->language} : '';
                            if ($v3['sys_node_only'] == 1)
                                $alias3 = ($v3['sys_alias']) ? $v3['sys_alias'] : '';
                            else
                                $alias3 = ($v3['alias']) ? json_decode($v3['alias'])->{Yii::$app->language} : '';
//var_dump($v3);//exit;
                            //                            $res[$k]['submenu'][$k2]['submenu'][$k3]['active'] = $current_alias === (($alias3 != 'home') ? $alias3 : '');
                            $res[$k]['submenu'][$k2]['submenu'][$k3]['active'] = false;
                            if ($current_alias === (($alias3 != 'home') ? $alias3 : ''))
                            {
                                $res[$k]['submenu'][$k2]['submenu'][$k3]['active'] = true;
                                $res[$k]['submenu'][$k2]['active'] = true;
                                $res[$k]['active'] = true;
                                //$res[$k]['submenu'][0]['active'] = true;
                            }

//  LEVEL  4
                            if ($v3['StructureID'] != '') { //var_dump('k2: '.$k2);
                                $query4 = "SELECT documentID AS ID, alias, MenuHeader, noFollow, noIndex, OrderIndex, NULL AS StructureID, ParentID, NULL AS submenu, '' AS icon, 0 AS sys_node_only,
        Header, Body_".Yii::$app->language." AS Body, '' AS sys_alias
        FROM ut_document WHERE (isPublic = 1) ".($menu_show > 0 ? 'AND (menu_show = 1)' : '')." AND (ParentID IN ('{$v3['StructureID']}'))
AND (OrderIndex > 0)
        UNION ALL
        SELECT 0 AS ID, NULL AS alias, sys_menu_header AS MenuHeader, 1 AS noFollow, 1 AS noIndex, OrderIndex, StructureID, ParentID, NULL AS submenu, '' AS icon, sys_node_only,
        '' AS Header, '' AS Body, sys_alias
        FROM sys_structure WHERE (ParentID IN ('{$v3['StructureID']}'))
        ORDER BY OrderIndex";
//var_dump($query4);exit;
                                $res4 = Yii::$app->db->createCommand($query4)->queryAll();    //  \PDO::FETCH_CLASS
                                $res[$k]['submenu'][$k2]['submenu'][$k3]['submenu'] = $res4;

                                foreach ($res4 as $k4 => $v4) {
                                    $id4 = ($v4['ID'] != 0) ? $v4['ID'] : $v4['StructureID'];

                                    //$alias3 = ($v3['alias']) ? json_decode($v3['alias'])->{Yii::$app->language} : '';
                                    if ($v4['sys_node_only'] == 1)
                                        $alias4 = ($v4['sys_alias']) ? $v4['sys_alias'] : '';
                                    else
                                        $alias4 = ($v4['alias']) ? json_decode($v4['alias'])->{Yii::$app->language} : '';
                                        //var_dump($v3);//exit;
                                        //                            $res[$k]['submenu'][$k2]['submenu'][$k3]['active'] = $current_alias === (($alias3 != 'home') ? $alias3 : '');
                                        $res[$k]['submenu'][$k2]['submenu'][$k3]['submenu'][$k4]['active'] = false;
                                        if ($current_alias === (($alias4 != 'home') ? $alias4 : ''))
                                        {
                                            $res[$k]['submenu'][$k2]['submenu'][$k3]['submenu'][$k4]['active'] = true;
                                            $res[$k]['submenu'][$k2]['submenu'][$k3]['active'] = true;
                                            $res[$k]['submenu'][$k2]['active'] = true;
                                            $res[$k]['active'] = true;
                                            //$res[$k]['submenu'][0]['active'] = true;
                                        }

                                }
                            }







                        }


                    }
















                }
            }
        }

        //var_dump($query);
        return $res;
    }


    public static function menuByBranch2($branch, $menu_show = 0)
    {
        $query = "SELECT ut_content.alias, MenuHeader, noFollow, noIndex, ut_content.ParentID, ut_content.footer FROM ut_content
            LEFT OUTER JOIN ut_footer_header ON (ut_content.footer = ut_footer_header.alias)
            WHERE (ut_content.isPublic = 1) ".($menu_show > 0 ? 'AND (menu_show = 1)' : '')." AND (ut_footer_header.menu_alias = '$branch')
            ORDER BY ut_footer_header.OrderIndex, ut_content.OrderIndex";
        $res = Yii::$app->db->createCommand($query)->queryAll();    //  \PDO::FETCH_CLASS
        return $res;
    }

    public static function menuFooter($parents = [])
    {
        $p = [];
        foreach ($parents as $k => $v) $p[$k] = "'".$v."'";

        $list = implode(',', $p);
        $query = "
            SELECT
            ut_content.alias,
            ut_content.MenuHeader,
            ut_content.noFollow,
            ut_content.noIndex,
            ut_content.ParentID,
            ut_content.site_branch
            FROM
            ut_content
            INNER JOIN sys_structure ON (ut_content.ParentID = sys_structure.StructureID)
            WHERE (ut_content.ParentID IN ($list))
            AND (ut_content.isPublic = 1)
            ORDER BY
            sys_structure.OrderIndex,
            ut_content.OrderIndex
            ";
        $res = Yii::$app->db->createCommand($query)->queryAll();    //  \PDO::FETCH_CLASS
        $items = [];
        if(count($parents) > 1){
            foreach($res as $item){
                $items[$item['ParentID']][] = $item;
            }
        }else{
            $items = $res;
        }
        return $items;
    }


    public static function scopeTop()
    {
        $query = "SELECT * FROM ut_scope WHERE (is_active = 1) ORDER BY sort_order";
        //var_dump($query);
        $res = Yii::$app->db->createCommand($query)->queryAll();    //  \PDO::FETCH_CLASS
        return $res;
    }
    public static function scopeByAlias($alias)
    {
        $query = "SELECT * FROM ut_scope WHERE (is_active = 1) AND (alias = '$alias') ORDER BY sort_order";
//var_dump($query);
        $res = Yii::$app->db->createCommand($query)->queryAll();    //  \PDO::FETCH_CLASS
        return $res;
    }

    public static function getProductList($scope_alias)
    {
        $query = "SELECT * FROM ut_product WHERE (scope_alias = '$scope_alias') AND (IFNULL(is_accompanying, 0) != 1) ORDER BY sort_order";
//var_dump($query);
        $res = Yii::$app->db->createCommand($query)->queryAll();    //  \PDO::FETCH_CLASS
        return $res;
    }



    public static function getTextWidget($alias, $lang = 'en')
    {
        $query = "SELECT body_{$lang} AS body FROM ut_text_widget WHERE (alias = '$alias')";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return $ret['body'];
    }

    public static function getCountryByAlias($alias, $lang = 'en')
    {
        $query = "SELECT
            json_get_clear(CountryCaption, 'ru') AS alias_ru,
            json_get_clear(CountryCaption, 'ro') AS alias_ro,
            json_get_clear(CountryCaption, 'en') AS alias_en
            FROM ut_country
            WHERE json_get_clear(CountryCaption, '$lang') = '$alias'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return $ret;
    }
    public static function getCityByAlias($alias, $lang = 'en')
    {
        $query = "SELECT
            json_get_clear(city_name, 'ru') AS alias_ru,
            json_get_clear(city_name, 'ro') AS alias_ro,
            json_get_clear(city_name, 'en') AS alias_en,
            city_code
            FROM ut_new_city
            WHERE json_get_clear(city_name, '$lang') = '$alias'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return $ret;
    }
    public static function getCityByCode($code, $lang = 'en')
    {
        $query = "SELECT
            json_get_clear(city_name, 'ru') AS alias_ru,
            json_get_clear(city_name, 'ro') AS alias_ro,
            json_get_clear(city_name, 'en') AS alias_en,
            city_code
            FROM ut_new_city
            WHERE city_code = '$code'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return $ret;
    }
    public static function getCityCaptionByCode($code, $lang = 'en')
    {
        $query = "SELECT
            json_get(city_name, 'ru') AS caption_ru,
            json_get(city_name, 'ro') AS caption_ro,
            json_get(city_name, 'en') AS caption_en,
            city_code
            FROM ut_new_city
            WHERE city_code = '$code'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return $ret;
    }

    public static function getChosenCityCodes($code = null)
    {
        $query = "SELECT city_code AS code,
            json_get_clear(city_name, 'ro') AS alias_ro,
            json_get_clear(city_name, 'ru') AS alias_ru,
            json_get_clear(city_name, 'en') AS alias_en
            FROM ut_new_city WHERE (isChosen = 1)";
        if ($code) $query .= " AND (city_code = '$code')";
        $ret = Yii::$app->db->createCommand($query)->queryAll();
        return $ret;
    }

    public static function getAirportByCode($code, $lang = 'en')
    {
        $query = "SELECT
            json_get_clear(dst_txt, 'ru') AS alias_ru,
            json_get_clear(dst_txt, 'ro') AS alias_ro,
            json_get_clear(dst_txt, 'en') AS alias_en
            FROM ut_new_aeroport
            WHERE dst_code = '$code'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return $ret;
    }
    public static function getAirportCaptionByCode($code, $lang = 'en')
    {
        $query = "SELECT
            json_get(dst_txt, 'ru') AS caption_ru,
            json_get(dst_txt, 'ro') AS caption_ro,
            json_get(dst_txt, 'en') AS caption_en,
            city_code
            FROM ut_new_aeroport
            WHERE dst_code = '$code'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return $ret;
    }
    public static function getAirportTZByCode($code, $lang = 'en')
    {
        $query = "SELECT TimeZone
            FROM ut_new_aeroport
            WHERE dst_code = '$code'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return $ret;
    }

    public static function getCarrierByCode($code, $lang = 'en')
    {
        $query = "SELECT
            json_get_clear(Caption, 'ru') AS alias_ru,
            json_get_clear(Caption, 'ro') AS alias_ro,
            json_get_clear(Caption, 'en') AS alias_en
            FROM ut_new_carrier
            WHERE code = '$code'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return $ret;
    }
    public static function getCarrierByAlias($alias, $lang = 'en')
    {
        $query = "SELECT
            json_get_clear(alias, 'ru') AS alias_ru,
            json_get_clear(alias, 'ro') AS alias_ro,
            json_get_clear(alias, 'en') AS alias_en
            FROM ut_new_carrier_content
            WHERE json_get_clear(alias, '$lang') = '$alias'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return $ret;
    }

    public static function getBranchByAlias($alias, $suffix = '/', $ln = null)
    {
        $lang = (!$ln) ? Yii::$app->language : $ln;
        $query = "SELECT json_get(alias, '$lang') AS alias FROM ut_site_branch WHERE sys_alias = '$alias'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return ($ret['alias'] != '') ? $ret['alias'].$suffix : '';
    }

    public static function getAliasesByAlias($alias, $suffix = '')
    {
        $lang = Yii::$app->language;
        $query = "SELECT
            json_get(alias, 'ru') AS alias_ru,
            json_get(alias, 'ro') AS alias_ro,
            json_get(alias, 'en') AS alias_en
            FROM ut_content
            WHERE json_get(alias, 'en') = '$alias'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
//        return ($ret['alias_ro'] != '') ? $ret['alias'].$suffix : '';
        return $ret['alias_'.$lang];
    }
    public static function getCaptionByAlias($alias, $suffix = '')
    {
        $lang = Yii::$app->language;
        $query = "SELECT
            caption
            FROM ut_site_branch
            WHERE sys_alias = '$alias'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        //        return ($ret['alias_ro'] != '') ? $ret['alias'].$suffix : '';
        return $ret['caption'];
    }

    public static function getBranchesByAlias($alias)
    {
        $lang = Yii::$app->language;
        $query = "SELECT alias FROM ut_site_branch WHERE sys_alias = '$alias'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return json_decode($ret['alias']);
    }
    public static function getBranchesByAliasLang($alias)
    {
        $lang = Yii::$app->language;
        $query = "SELECT alias FROM ut_site_branch WHERE json_get(alias, '$lang') = '$alias'";
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        return json_decode($ret['alias']);
    }
/*
    public static function newsAlias()
    {
        $alias = Content::find()
            ->select(['alias'])
            ->andWhere(['ut_content.ParentID' => '24609de2e5606aba7752b21dcb973aa8'])
            ->andWhere(['ut_content.isPublic' => 1])
            ->andWhere(['ut_content.Special_Pattern' => 'news_line'])
            ->one();
        return isset($alias->alias) ? Lang::translate($alias->alias) : null;
    }
    public static function offersAlias()
    {
    	$alias = Content::find()
    	->select(['alias'])
    	->andWhere(['ut_content.ParentID' => '24609de2e5606aba7752b21dcb973aa8'])
    	->andWhere(['ut_content.isPublic' => 1])
    	->andWhere(['ut_content.Special_Pattern' => 'offers_line'])
    	->one();
    	return isset($alias->alias) ? Lang::translate($alias->alias) : null;
    }
*/
    public static function homeAlias($branch='', $alias='', $alias2='', $city='', $city2='', $code='', $code2='', $country='', $airport='', $direction='')
    {
        switch ($direction)
        {
            case "from":
            case "din":
            case "из":
                $rdirection = 1;
            break;

            default:
            case "to":
            case "spre":
            case "в":
                $rdirection = 0;
            break;
        }

//var_dump($branch, $alias, $alias2, $city, $city2, $code, $code2, $country, $airport, $direction ); exit;

        if (!empty($country)) $alias = $country;
    	if ($alias == '')
    	{
    		$alias_res = array(0 => array('alias_ru'=> '', 'alias_ro'=> '', 'alias_en'=> ''));
    	}

    	if ($branch == '' && $alias != '' && $alias2 != '')
    	{
    	    $alias_res = Content::getContentPage2($alias2, '', '', $code);
            $alias_res['site_branch'] = 'airline-tickets';
//var_dump('AAA1: ', $alias_res);
            $b = Content::getBranchesByAlias($alias_res['site_branch']);
            $c = Content::getCountryByAlias($country, Yii::$app->language);
//var_dump('BBB1: ', $c);
$b_ru = (isset($b->ru)) ? $b->ru.'/' : '';
$b_ro = (isset($b->ro)) ? $b->ro.'/' : '';
$b_en = (isset($b->en)) ? $b->en.'/' : '';

if ( ($alias == 'дешевые-авиабилеты') || ($alias == 'bilete-avion') || ($alias == 'airline-tickets') )
{
    $alias_res = array(0 => array(
        'alias_ru'=> $b->ru.'/'.mb_strtolower($alias_res['alias_ru']).'-'.$code,
        'alias_ro'=> $b->ro.'/'.mb_strtolower($alias_res['alias_ro']).'-'.$code,
        'alias_en'=> $b->en.'/'.mb_strtolower($alias_res['alias_en']).'-'.$code,
    ));
}
else
{
    $alias_res = array(0 => array(
        'alias_ru'=> $b->ru.'/'.$alias_res['alias_ru'].($rdirection ? '-из-' : '-в-').$c['alias_ru'],
        'alias_ro'=> $b->ro.'/'.$alias_res['alias_ro'].($rdirection ? '-din-' : '-spre-').$c['alias_ro'],
        'alias_en'=> $b->en.'/'.$alias_res['alias_en'].($rdirection ? '-from-' : '-to-').$c['alias_en']
    ));
}
//$alias_res = array(0 => array('alias_ru'=> '', 'alias_ro'=> '', 'alias_en'=> ''));

    	}
    	else if ($branch == '' && $alias != '' && $city != '')
    	{
    	    $alias_res = Content::getContentPage2($alias, $city, '');
    	    $alias_res['site_branch'] = 'flights';
//var_dump('AAA1: ', $alias_res);
    	    $b = Content::getBranchesByAlias($alias_res['site_branch']);
    	    $c = Content::getCityByCode($code, Yii::$app->language);
//var_dump('BBB1: ', $b, $c);
    	    $b_ru = (isset($b->ru)) ? $b->ru.'/' : '';
    	    $b_ro = (isset($b->ro)) ? $b->ro.'/' : '';
    	    $b_en = (isset($b->en)) ? $b->en.'/' : '';
    	    $alias_res = array(0 => array(
    	        'alias_ru'=> $b->ru.'/'.($rdirection ? 'из-' : 'в-').$c['alias_ru'].'-'.strtolower($c['city_code']),
    	        'alias_ro'=> $b->ro.'/'.($rdirection ? 'din-' : 'spre-').$c['alias_ro'].'-'.strtolower($c['city_code']),
    	        'alias_en'=> $b->en.'/'.($rdirection ? 'from-' : 'to-').$c['alias_en'].'-'.strtolower($c['city_code'])
    	    ));
    	    //$alias_res = array(0 => array('alias_ru'=> '', 'alias_ro'=> '', 'alias_en'=> ''));
    	}
    	else if ($branch == '' && $alias != '' && $airport != '')
    	{
    	    $alias_res = Content::getContentPage2($alias, $city, $airport);
    	    $alias_res['site_branch'] = 'cheap-flight';
//var_dump('AAA1: ', $alias_res);
    	    $b = Content::getBranchesByAlias($alias_res['site_branch']);
    	    $c = Content::getAirportByCode($code, Yii::$app->language);
//var_dump('BBB1: ', $b, $c);
    	    $b_ru = (isset($b->ru)) ? $b->ru.'/' : '';
    	    $b_ro = (isset($b->ro)) ? $b->ro.'/' : '';
    	    $b_en = (isset($b->en)) ? $b->en.'/' : '';
    	    $alias_res = array(0 => array(
    	        'alias_ru'=> $b->ru.'/'.($rdirection ? 'из-' : 'в-').$c['alias_ru'].'-'.$code,
    	        'alias_ro'=> $b->ro.'/'.($rdirection ? 'din-' : 'spre-').$c['alias_ro'].'-'.$code,
    	        'alias_en'=> $b->en.'/'.($rdirection ? 'from-' : 'to-').$c['alias_en'].'-'.$code
    	    ));
    	    //$alias_res = array(0 => array('alias_ru'=> '', 'alias_ro'=> '', 'alias_en'=> ''));
    	}
    	else if ($branch != '' && $alias == '' && $code != '' && $code2 != '')
    	{
    	    $alias_res = Content::getContentPage2($alias, $city, '');
    	    $alias_res['site_branch'] = 'flights';
//var_dump('AAA: ', $alias_res);

    	    $b = Content::getBranchesByAlias($alias_res['site_branch']);

    	    $type = 'port';
    	    $c = Content::getAirportByCode($code, Yii::$app->language);
    	    if (!$c) { $type = 'city'; $c = Content::getCityByCode($code, Yii::$app->language); }
//var_dump('BBB1: ', $c);
            $c2 = Content::getAirportByCode($code2, Yii::$app->language);
            if (!$c2) $c2 = Content::getCityByCode($code2, Yii::$app->language);
//var_dump('BBB2: ', $c2);

    	    $b_ru = (isset($b->ru)) ? $b->ru.'/' : '';
    	    $b_ro = (isset($b->ro)) ? $b->ro.'/' : '';
    	    $b_en = (isset($b->en)) ? $b->en.'/' : '';

            switch ($type)
            {
                case 'port':
            	    $alias_res = array(0 => array(
        	        'alias_ru'=> $b->ru.'/'.(!$rdirection ? 'из-' : 'в-').$c['alias_ru'].'-'.$code.'/'.($rdirection ? 'из-' : 'в-').$c2['alias_ru'].'-'.$code2,
        	        'alias_ro'=> $b->ro.'/'.(!$rdirection ? 'din-' : 'spre-').$c['alias_ro'].'-'.$code.'/'.($rdirection ? 'din-' : 'spre-').$c2['alias_ro'].'-'.$code2,
        	        'alias_en'=> $b->en.'/'.(!$rdirection ? 'from-' : 'to-').$c['alias_en'].'-'.$code.'/'.($rdirection ? 'from-' : 'to-').$c2['alias_en'].'-'.$code2
        	    ));
    	    //$alias_res = array(0 => array('alias_ru'=> '', 'alias_ro'=> '', 'alias_en'=> ''));
    	       break;
               case 'city':
                    $alias_res = array(0 => array(
                    'alias_ru'=> $b->ru.'/'.(!$rdirection ? 'из-' : 'в-').$c['alias_ru'].'-'.$code.'/'.($rdirection ? 'из-' : 'в-').$c2['alias_ru'].'-'.$code2,
                    'alias_ro'=> $b->ro.'/'.(!$rdirection ? 'din-' : 'spre-').$c['alias_ro'].'-'.$code.'/'.($rdirection ? 'din-' : 'spre-').$c2['alias_ro'].'-'.$code2,
                    'alias_en'=> $b->en.'/'.(!$rdirection ? 'from-' : 'to-').$c['alias_en'].'-'.$code.'/'.($rdirection ? 'from-' : 'to-').$c2['alias_en'].'-'.$code2
                    ));
                    //$alias_res = array(0 => array('alias_ru'=> '', 'alias_ro'=> '', 'alias_en'=> ''));
               break;
            }
        }
    	else
    	{
    	    $mode = 'page';
    	    $b = Content::getBranchesByAliasLang($branch);

            if ($b && $b->en == 'flight-tickets' && $alias == '')
            {
                $mode = 'carrier';
        	    $alias_res = Content::getCarrierByCode($code, Yii::$app->language);
//var_dump('AAA1: ', $alias_res); exit;
//        	    if (!$alias_res) {
//        	        $alias_res = Content::getContentPage2($alias, '', '');
//        	    } else {
        	        //$alias_res['site_branch'] = '';
//        	        $alias_res = array(0 => array('alias_ru'=> '', 'alias_ro'=> '', 'alias_en'=> ''));
//        	    }
//var_dump('AAA2: ', $alias_res); //exit;
                //$b = Content::getBranchesByAlias($branch);
//var_dump('BBB2: ', $b); //exit;
            }
            else
            {
                $alias_res = Content::getCarrierByAlias($alias, Yii::$app->language);
                if (!$alias_res) {
                    $alias_res = Content::getContentPage2($alias, '', '');
                } else {
                    $alias_res['site_branch'] = $branch;
                }
//var_dump('AAA2: ', $alias, $alias_res);
                if ($alias == '') $alias_res = array('alias_ru'=> '', 'alias_ro'=> '', 'alias_en'=> '');

                if ( !$branch && isset($alias_res['site_branch'])) $b = Content::getBranchesByAlias($alias_res['site_branch']);
                else $b = Content::getBranchesByAlias($branch);
//var_dump($alias_res); //exit;
//var_dump('BBB2: ', $b); //exit;

            }
            $b_ru = (isset($b->ru)) ? $b->ru.'/' : '';
            $b_ro = (isset($b->ro)) ? $b->ro.'/' : '';
            $b_en = (isset($b->en)) ? $b->en.'/' : '';
            /*
            $f = Content::getBranchesByAlias('from');
            $f_ru = (isset($f->ru)) ? $f->ru.'-' : '';
            $f_ro = (isset($f->ro)) ? $f->ro.'-' : '';
            $f_en = (isset($f->en)) ? $f->en.'-' : '';
            $t = Content::getBranchesByAlias('to');
            $t_ru = (isset($t->ru)) ? $t->ru.'-' : '';
            $t_ro = (isset($t->ro)) ? $t->ro.'-' : '';
            $t_en = (isset($t->en)) ? $t->en.'-' : '';
*/

            if ( ($branch != '') && ($alias != '') && ($code == '') && ($direction == '') && ($code2 == '') )
            {
                $b = Content::getBranchesByAliasLang($branch);

                $alias_res = array(0 => array(
                    'alias_ru'=> $b->ru.'/'.$alias_res['alias_ru'],
                    'alias_ro'=> $b->ro.'/'.$alias_res['alias_ro'],
                    'alias_en'=> $b->en.'/'.$alias_res['alias_en']));
            }
            else if ( ($branch != '') && ($alias != '') && ($code != '') && ($direction != '') && ($code2 == '') )
            {

                $b = Content::getBranchesByAliasLang($branch);
                $alias_res = array(0 => array(
                    'alias_ru'=> $b->ru.'/'.$alias_res['alias_ru'],
                    'alias_ro'=> $b->ro.'/'.$alias_res['alias_ro'],
                    'alias_en'=> $b->en.'/'.$alias_res['alias_en']));
            }
            else if ($mode == 'carrier')
            {
                $alias_res = array(0 => array(
                    'alias_ru'=> $b->ru.'/'.$alias_res['alias_ru'].'-'.$code,
                    'alias_ro'=> $b->ro.'/'.$alias_res['alias_ro'].'-'.$code,
                    'alias_en'=> $b->en.'/'.$alias_res['alias_en'].'-'.$code));
            }
            else
            {
//                var_dump($alias_res); exit;
                if ($alias_res)
                {
                $alias_res['alias_ru'] = $b_ru.$alias_res['alias_ru'];
                $alias_res['alias_ro'] = $b_ro.$alias_res['alias_ro'];
                $alias_res['alias_en'] = $b_en.$alias_res['alias_en'];
                $alias_res = array(0 => $alias_res);
                }
                else
                    $alias_res = array(0 => array('alias_ru'=> '', 'alias_ro'=> '', 'alias_en'=> ''));
            }
/*
    		if (!$alias_res)
    		{
  				$r = Content::getContentPage3($alias, $city, $airport);
				if ($city != '')
				{
					if ($airport != '')
					{
						$alias_res = array(0 => array(
							'alias_ru' => $r['country_alias_ru']."/".$r['city_alias_ru'].(($r['state_alias_ru'] != '') ? '-'.$r['state_alias_ru'] : '')."/".$r['airport_alias_ru'],
							'alias_ro' => $r['country_alias_ro']."/".$r['city_alias_ro'].(($r['state_alias_ro'] != '') ? '-'.$r['state_alias_ro'] : '')."/".$r['airport_alias_ro'],
							'alias_en' => $r['country_alias_en']."/".$r['city_alias_en'].(($r['state_alias_en'] != '') ? '-'.$r['state_alias_en'] : '')."/".$r['airport_alias_en']));
					}
					else
					{
						$alias_res = array(0 => array(
							'alias_ru'=> $r['country_alias_ru'].'/'.$r['city_alias_ru'].(($r['state_alias_ru'] != '') ? '-'.$r['state_alias_ru'] : ''),
							'alias_ro'=> $r['country_alias_ro'].'/'.$r['city_alias_ro'].(($r['state_alias_ro'] != '') ? '-'.$r['state_alias_ro'] : ''),
							'alias_en'=> $r['country_alias_en'].'/'.$r['city_alias_en'].(($r['state_alias_en'] != '') ? '-'.$r['state_alias_en'] : '')));
					}
				}
				else
				{
					$alias_res = array(0 => array(
						'alias_ru'=> $r['country_alias_ru'],
						'alias_ro'=> $r['country_alias_ro'],
						'alias_en'=> $r['country_alias_en']));
				}
    		}
    		else
    		{

    		    $alias_res['alias_ru'] = $b_ru.$alias_res['alias_ru'];
    		    $alias_res['alias_ro'] = $b_ro.$alias_res['alias_ro'];
    		    $alias_res['alias_en'] = $b_en.$alias_res['alias_en'];
    			$alias_res = array(0 => $alias_res);
    		}   */
    	}
//var_dump('RRR: ', $alias_res);
    	unset($alias_res[0]['body']);
    	return $alias_res;
    }


    public static function getListByParent($parent_id)
    {
        $lang = Yii::$app->language;

        $query = "SELECT
              json_get(ut_document.alias, '$lang') AS alias,
/*              ut_document.alias AS aliases,*/
              json_get(ut_document.MenuHeader, '$lang') AS header
        	FROM ut_document
            INNER JOIN sys_structure ON (ut_document.ParentID = sys_structure.StructureID)
            WHERE (ut_document.isPublic = 1)
            AND (ut_document.menu_show = 1)
            AND (sys_structure.is_active = 1)
            AND (ut_document.ParentID = '$parent_id')
            AND (ut_document.OrderIndex > 0)
            ORDER BY ut_document.OrderIndex";

        //$query = str_replace(['@@lang@@', '@@aliases@@'], [$lang, $alias], $query);
//var_dump($query); //exit;
        return Yii::$app->db->createCommand($query)->queryAll();
    }


    public static function getContentPage($alias, $lang = 'ro', $ext = false)
    {
        //$lang = Yii::$app->language;

        $query = "SELECT ParentID AS parent_id,
              json_get(ut_document.alias, '@@lang@@') AS alias,
              ut_document.alias AS aliases,
              json_get(ut_document.Header, '@@lang@@') AS header,
              ut_document.Body_@@lang@@ AS body,
              ut_document.Note_@@lang@@ AS note,
              ut_document.ImageURL AS image,
              ut_document.public_date,
              ut_document.last_update,
              json_get(ut_document.Title, '@@lang@@') AS seo_title,
              json_get(ut_document.Descr, '@@lang@@') AS seo_description,
              json_get(ut_document.Keywords, '@@lang@@') AS seo_keywords,
              ut_document.noIndex AS no_index,
              ut_document.OrderIndex AS order_index,
        	  json_get(ut_document.ImageAlt, '@@lang@@') AS image_alt,
        	  json_get(ut_document.ImageTitle, '@@lang@@') AS image_title,
        	  ut_document.ImageAltShow AS image_alt_show,
        	  ut_document.ImageTitleShow AS image_title_show,
        	  ut_document.noFollow AS no_follow,
        	  ut_document.noIndex AS no_index,
        	  ut_document.canonical_link
/*              ut_document.site_branch*/
        	FROM
        	ut_document
            WHERE ut_document.isPublic = 1
            AND ut_document.alias LIKE '%@@aliases@@%'
            AND json_get(ut_document.alias, '@@lang@@') = '@@aliases@@'
";
        if (!$ext) $query .= "            AND ut_document.OrderIndex > 0
";
        $query .= "
            ORDER BY ut_document.OrderIndex";

        $query = str_replace(['@@lang@@', '@@aliases@@'], [$lang, $alias], $query);
//var_dump($query); exit;
        return Yii::$app->db->createCommand($query)->queryOne();
    }

    public static function getContentHelp($alias)
    {
        $lang = Yii::$app->language;
        $query = "SELECT ParentID AS parent_id,
              json_get(alias, '@@lang@@') AS alias,
              json_get(Header, '@@lang@@') AS header,
              Body_@@lang@@ AS body,
              Help_@@lang@@ AS help,
              last_update,
              IFNULL(help_show, 0) AS help_show
        	FROM ut_document
            WHERE ut_document.isPublic = 1
            AND help_alias = '@@aliases@@'";
        $query = str_replace(['@@lang@@', '@@aliases@@'], [$lang, $alias], $query);
//var_dump($query); exit;
        $ret = Yii::$app->db->createCommand($query)->queryOne();
        if ($ret) {
            if ($ret['help_show'] == '1') $ret['help'] = Macros::process((strlen($ret['help']) > 6) ? $ret['help'] : $ret['body']);
            else $ret['help'] = Macros::process($ret['body']);
            unset($ret['body']);
        }
        return $ret;
    }



    public static function getSearchPage($search)
    {
        $lang = Yii::$app->language;

        $query = "SELECT ParentID AS parent_id,
              json_get(ut_document.alias, '@@lang@@') AS alias,
              ut_document.alias AS aliases,
              json_get(ut_document.Header, '@@lang@@') AS header,
              ut_document.Body_@@lang@@ AS body,
              ut_document.Note_@@lang@@ AS note,
              ut_document.ImageURL AS image,
              ut_document.public_date,
              ut_document.last_update,
              json_get(ut_document.Title, '@@lang@@') AS seo_title,
              json_get(ut_document.Descr, '@@lang@@') AS seo_description,
              json_get(ut_document.Keywords, '@@lang@@') AS seo_keywords,
              ut_document.noIndex AS no_index,
              ut_document.OrderIndex AS order_index,
        	  json_get(ut_document.ImageAlt, '@@lang@@') AS image_alt,
        	  json_get(ut_document.ImageTitle, '@@lang@@') AS image_title,
        	  ut_document.ImageAltShow AS image_alt_show,
        	  ut_document.ImageTitleShow AS image_title_show,
        	  ut_document.noFollow AS no_follow,
        	  ut_document.noIndex AS no_index,
        	  ut_document.canonical_link
        	FROM
        	ut_document
            WHERE ut_document.isPublic = 1
            AND json_get(ut_document.Header, '@@lang@@') LIKE '%@@search@@%'
            /*AND json_get(ut_document.alias, '@@lang@@') = '@@search@@'*/
            ORDER BY ut_document.OrderIndex";

        $query = str_replace(['@@lang@@', '@@search@@'], [$lang, $search], $query);
        //var_dump($query); //exit;
        return Yii::$app->db->createCommand($query)->queryAll();
    }



    public static function getContentPage2($alias, $city = '', $airport = '', $continent = '')
    {
        $lang = Yii::$app->language;

        $query = "SELECT * FROM (
                SELECT
                'ut_content' AS tablename,
        		'' AS code,
                json_get(Header, '@@lang@@') AS header,
                json_get(Title, '@@lang@@') AS title,
                json_get(Descr, '@@lang@@') AS description,
                json_get(Keywords, '@@lang@@') AS keywords,
        		Body_@@lang@@ AS body,
        		ImageURL AS image,
        		json_get(ut_content.ImageAlt, '@@lang@@') AS image_alt,
        		json_get(ut_content.ImageTitle, '@@lang@@') AS image_title,
        		ut_content.ImageAltShow AS image_alt_show,
        		ut_content.ImageTitleShow AS image_title_show,
                1 AS priority,
        		json_get(ut_content.Alias, '@@lang@@') AS alias,
        		json_get(ut_content.Alias, 'ru') AS alias_ru,
        		json_get(ut_content.Alias, 'ro') AS alias_ro,
        		json_get(ut_content.Alias, 'en') AS alias_en,
                ut_content.site_branch,
        	    ut_content.noFollow AS no_follow,
        	    ut_content.noIndex AS no_index,
        		'' AS moment
                FROM ut_content
                WHERE (ut_content.Alias LIKE \"%@@aliases@@%\") AND (json_get(ut_content.Alias, '@@lang@@') = '@@aliases@@') AND (isPublic = 1)

                UNION ALL
                SELECT
                'ut_news_line' AS tablename,
                'news' AS pattern,
        		'' AS code,
                json_get(Header, '@@lang@@') AS header,
                json_get(Title, '@@lang@@') AS title,
                json_get(Description, '@@lang@@') AS description,
                '' AS keywords,
        		body_@@lang@@ AS body,
        		BigImageURL AS image,
        		json_get(ut_news_line.ImageAlt, '@@lang@@') AS image_alt,
        		json_get(ut_news_line.ImageTitle, '@@lang@@') AS image_title,
        		ut_news_line.ImageAltShow AS image_alt_show,
        		ut_news_line.ImageTitleShow AS image_title_show,
                2 AS priority,
        		json_get(ut_news_line.Alias, '@@lang@@') AS alias,
        		json_get(ut_news_line.Alias, 'ru') AS alias_ru,
        		json_get(ut_news_line.Alias, 'ro') AS alias_ro,
        		json_get(ut_news_line.Alias, 'en') AS alias_en,
                '' AS site_branch,
        	    1 AS no_follow,
        	    1 AS no_index,
        		moment
                FROM ut_news_line
                WHERE (Alias LIKE \"%@@aliases@@%\") AND (json_get(Alias, '@@lang@@') = '@@aliases@@') AND (is_public = 1)

                UNION ALL
                SELECT
                'ut_new_carrier_content' AS tablename,
                'carrier' AS pattern,
        		ANSI2 AS code,
                json_get(Header, '@@lang@@') AS header,
                json_get(Title, '@@lang@@') AS title,
                json_get(Descr, '@@lang@@') AS description,
                json_get(Keywords, '@@lang@@') AS keywords,
        		Body_@@lang@@ AS body,
        		/*IF(ImageURL2 <> '', ImageURL2, ImageURL1) AS image,*/
        		ImageURL1 AS image,
        		json_get(ut_new_carrier_content.ImageAlt, '@@lang@@') AS image_alt,
        		json_get(ut_new_carrier_content.ImageTitle, '@@lang@@') AS image_title,
        		ut_new_carrier_content.ImageAltShow AS image_alt_show,
        		ut_new_carrier_content.ImageTitleShow AS image_title_show,
                3 AS priority,
        		json_get(ut_new_carrier_content.Alias, '@@lang@@') AS alias,
        		json_get(ut_new_carrier_content.Alias, 'ru') AS alias_ru,
        		json_get(ut_new_carrier_content.Alias, 'ro') AS alias_ro,
        		json_get(ut_new_carrier_content.Alias, 'en') AS alias_en,
                '' AS site_branch,
        	    ut_new_carrier_content.noFollow AS no_follow,
        	    ut_new_carrier_content.noIndex AS no_index,
        		'' AS moment
                FROM ut_new_carrier_content
                WHERE (ut_new_carrier_content.Alias LIKE \"%@@aliases@@%\") AND (json_get(ut_new_carrier_content.Alias, '@@lang@@') = '@@aliases@@') AND (isPublic = 1)
";



		$query .= ") tmp
                ORDER BY priority
                LIMIT 1";

		$query = str_replace(['@@lang@@', '@@aliases@@', '@@cities@@', '@@airports@@'], [$lang, $alias, $city, $airport], $query);
//var_dump($alias, $city, $airport, $query); exit;
		return Yii::$app->db->createCommand($query)->queryOne();
    }

    public static function getContentPageCountry($alias, $direction = 0)
    {
        $lang = Yii::$app->language;

        $query = "
				SELECT
                'ut_new_country_content' AS tablename,
                'country' AS pattern,
				'' AS code,
                json_get(Header, '@@lang@@') AS header,
                json_get(Title, '@@lang@@') AS title,
                json_get(Descr, '@@lang@@') AS description,
                json_get(Keywords, '@@lang@@') AS keywords,
              	ut_new_country_content.Special_@@lang@@ AS special_content,
              	ut_new_country_content.Special_Type AS special_type,
              	ut_special_type.open_tag,
              	ut_special_type.close_tag,
        		Body_@@lang@@ AS body,
				ImageURL AS image,
        		json_get(ut_new_country_content.ImageAlt, '@@lang@@') AS image_alt,
        		json_get(ut_new_country_content.ImageTitle, '@@lang@@') AS image_title,
        		ut_new_country_content.ImageAltShow AS image_alt_show,
        		ut_new_country_content.ImageTitleShow AS image_title_show,
                6 AS priority,
				json_get(ut_new_country_content.Alias, '@@lang@@') AS alias,
        		json_get(ut_new_country_content.Alias, 'ru') AS alias_ru,
        		json_get(ut_new_country_content.Alias, 'ro') AS alias_ro,
        		json_get(ut_new_country_content.Alias, 'en') AS alias_en,
        	    ut_new_country_content.noFollow AS no_follow,
        	    ut_new_country_content.noIndex AS no_index,
				'' AS moment,
                'airline-tickets' AS site_branch
                FROM ut_new_country_content
				LEFT OUTER JOIN ut_special_type ON (ut_new_country_content.Special_Type = ut_special_type.alias)
                WHERE (ut_new_country_content.Alias LIKE \"%@@aliases@@%\") AND (json_get(ut_new_country_content.Alias, '@@lang@@') = '@@aliases@@')
                AND (isPublic = 1) AND (IFNULL(flag_from, 0) = {$direction})
                LIMIT 1";

        $query = str_replace(['@@lang@@', '@@aliases@@'], [$lang, $alias], $query);
//var_dump($query); exit;
        return Yii::$app->db->createCommand($query)->queryOne();
    }

    public static function getContentPageCity($code, $direction = 0)
    {
        $lang = Yii::$app->language;
//var_dump($code);exit;
        $query = "
        		SELECT
                'ut_new_city_content' AS tablename,
                'city' AS pattern,
        		'' AS code,
                json_get(Header, '@@lang@@') AS header,
                json_get(Title, '@@lang@@') AS title,
                json_get(Descr, '@@lang@@') AS description,
                json_get(Keywords, '@@lang@@') AS keywords,
              	ut_new_city_content.Special_@@lang@@ AS special_content,
              	ut_new_city_content.Special_Type AS special_type,
              	ut_special_type.open_tag,
              	ut_special_type.close_tag,
        		Body_@@lang@@ AS body,
        		ImageURL AS image,
        		json_get(ut_new_city_content.ImageAlt, '@@lang@@') AS image_alt,
        		json_get(ut_new_city_content.ImageTitle, '@@lang@@') AS image_title,
        		ut_new_city_content.ImageAltShow AS image_alt_show,
        		ut_new_city_content.ImageTitleShow AS image_title_show,
                5 AS priority,
        		json_get(ut_new_city_content.Alias, '@@lang@@') AS alias,
        		json_get(ut_new_city_content.Alias, 'ru') AS alias_ru,
        		json_get(ut_new_city_content.Alias, 'ro') AS alias_ro,
        		json_get(ut_new_city_content.Alias, 'en') AS alias_en,
        	    ut_new_city_content.noFollow AS no_follow,
        	    ut_new_city_content.noIndex AS no_index,
        		'' AS moment
                FROM ut_new_city_content
        		LEFT OUTER JOIN ut_special_type ON (ut_new_city_content.Special_Type = ut_special_type.alias)
                WHERE (ut_new_city_content.city_code = \"@@code@@\") AND (isPublic = 1) AND (IFNULL(flag_from, 0) = {$direction})
                LIMIT 1";

                    $query = str_replace(['@@lang@@', '@@code@@'], [$lang, $code], $query);
//var_dump($query); exit;
                    return Yii::$app->db->createCommand($query)->queryOne();
    }

    public static function getContentPageAirport($code, $direction = 0)
    {
        $lang = Yii::$app->language;

        $query = "
        		SELECT
                'ut_new_aeroport_content' AS tablename,
                'airport' AS pattern,
        		'' AS code,
                json_get(Header, '@@lang@@') AS header,
                json_get(Title, '@@lang@@') AS title,
                json_get(Descr, '@@lang@@') AS description,
                json_get(Keywords, '@@lang@@') AS keywords,
              	ut_new_aeroport_content.Special_@@lang@@ AS special_content,
              	ut_new_aeroport_content.Special_Type AS special_type,
              	ut_special_type.open_tag,
              	ut_special_type.close_tag,
        		Body_@@lang@@ AS body,
        		ImageURL AS image,
        		json_get(ut_new_aeroport_content.ImageAlt, '@@lang@@') AS image_alt,
        		json_get(ut_new_aeroport_content.ImageTitle, '@@lang@@') AS image_title,
        		ut_new_aeroport_content.ImageAltShow AS image_alt_show,
        		ut_new_aeroport_content.ImageTitleShow AS image_title_show,
                4 AS priority,
        		json_get(ut_new_aeroport_content.Alias, '@@lang@@') AS alias,
        		json_get(ut_new_aeroport_content.Alias, 'ru') AS alias_ru,
        		json_get(ut_new_aeroport_content.Alias, 'ro') AS alias_ro,
        		json_get(ut_new_aeroport_content.Alias, 'en') AS alias_en,
        	    ut_new_aeroport_content.noFollow AS no_follow,
        	    ut_new_aeroport_content.noIndex AS no_index,
        		'' AS moment
                FROM ut_new_aeroport_content
        		LEFT OUTER JOIN ut_special_type ON (ut_new_aeroport_content.Special_Type = ut_special_type.alias)
                WHERE (ut_new_aeroport_content.AirportCode LIKE \"@@code@@\") AND (isPublic = 1) AND (IFNULL(flag_from, 0) = {$direction})
        		";

        $query = str_replace(['@@lang@@', '@@code@@'], [$lang, $code], $query);
//var_dump($query);
        return Yii::$app->db->createCommand($query)->queryOne();
    }

    public static function getContentPageCarrier($code)
    {
        $lang = Yii::$app->language;

        $query = "
        		SELECT
                'ut_new_aeroport_content' AS tablename,
                'airport' AS pattern,
        		'' AS code,
                json_get(Header, '@@lang@@') AS header,
                json_get(Title, '@@lang@@') AS title,
                json_get(Descr, '@@lang@@') AS description,
                json_get(Keywords, '@@lang@@') AS keywords,
              	ut_new_aeroport_content.Special_@@lang@@ AS special_content,
              	ut_new_aeroport_content.Special_Type AS special_type,
              	ut_special_type.open_tag,
              	ut_special_type.close_tag,
        		Body_@@lang@@ AS body,
        		ImageURL AS image,
        		json_get(ut_new_aeroport_content.ImageAlt, '@@lang@@') AS image_alt,
        		json_get(ut_new_aeroport_content.ImageTitle, '@@lang@@') AS image_title,
        		ut_new_aeroport_content.ImageAltShow AS image_alt_show,
        		ut_new_aeroport_content.ImageTitleShow AS image_title_show,
                4 AS priority,
        		json_get(ut_new_aeroport_content.Alias, '@@lang@@') AS alias,
        		json_get(ut_new_aeroport_content.Alias, 'ru') AS alias_ru,
        		json_get(ut_new_aeroport_content.Alias, 'ro') AS alias_ro,
        		json_get(ut_new_aeroport_content.Alias, 'en') AS alias_en,
        	    ut_new_aeroport_content.noFollow AS no_follow,
        	    ut_new_aeroport_content.noIndex AS no_index,
        		'' AS moment
                FROM ut_new_aeroport_content
        		LEFT OUTER JOIN ut_special_type ON (ut_new_aeroport_content.Special_Type = ut_special_type.alias)
                WHERE
        		(ut_new_aeroport_content.AirportCode LIKE \"@@code@@\") AND (isPublic = 1)
        		";

        $query = str_replace(['@@lang@@', '@@code@@'], [$lang, $code], $query);
        //var_dump($query);
        return Yii::$app->db->createCommand($query)->queryOne();
    }


    public static function getContentPage3($alias, $city = '', $airport = '')
    {
        //TODO city может быть пустым при zbor/din-vnucovo-vko, нужно учесть это
    	$lang = Yii::$app->language;
    	$query = sprintf("CALL getGeoData('%s', '%s', '%s', '%s')", $lang, $alias, $city, $airport);
//var_dump($query);
     	return Yii::$app->db->createCommand($query)->queryOne();
    }

    public static function getContentPageCityByCode($code)
    {
        $lang = Yii::$app->language;
        $query = sprintf("CALL getGeoDataCityByCode('%s', '%s')", $lang, $code);
        return Yii::$app->db->createCommand($query)->queryOne();
    }

    public static function getContentPageAirportByCode($code)
    {
        $lang = Yii::$app->language;
        $query = sprintf("CALL getGeoDataAirportByCode('%s', '%s')", $lang, $code);
        return Yii::$app->db->createCommand($query)->queryOne();
    }

    public static function getContentPageCarrierByCode($code)
    {
        $lang = Yii::$app->language;
        $query = sprintf("CALL getGeoDataCarrierByCode('%s', '%s')", $lang, $code);
        return Yii::$app->db->createCommand($query)->queryOne();
    }

    public static function replaceData($dresult, $item)
    {
        return stripslashes(str_replace(['@@site@@', '@@country@@', '@@country_alt@@', '@@country_code@@', '@@city@@', '@@city_alt@@', '@@city_code@@', '@@airport@@', '@@station@@', '@@airport_code@@', '@@lat@@', '@@lng@@', '@@carrier@@', '@@carrier_code@@'],
            ['avia.md', (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''), (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''), (isset($dresult['country_ansi2']) ? $dresult['country_ansi2'] : ''), $dresult['city_caption'], $dresult['city_caption'], $dresult['city_code'], $dresult['airport_caption'], $dresult['airport_caption'], $dresult['airport_code'], $dresult['Latitude'], $dresult['Longitude'], (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''), (isset($dresult['country_ansi2']) ? $dresult['country_ansi2'] : '')],
            $item));
    }
    public static function replaceSanitizedData($dresult, $item)
    {
    	$r = str_replace(['@@site@@', '@@country@@', '@@country_alt@@', '@@country_code@@', '@@city@@', '@@city_alt@@', '@@city_code@@', '@@airport@@', '@@station@@', '@@airport_code@@', '@@lat@@', '@@lng@@', '@@carrier@@', '@@carrier_code@@'],
    	    ['avia.md', (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''), (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''), (isset($dresult['country_ansi2']) ? $dresult['country_ansi2'] : ''), $dresult['city_caption'], $dresult['city_caption'], $dresult['city_code'], $dresult['airport_caption'], $dresult['airport_caption'], $dresult['airport_code'], $dresult['Latitude'], $dresult['Longitude'], (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''), (isset($dresult['country_ansi2']) ? $dresult['country_ansi2'] : '')],
    		$item);
   		return Content::sanitizeData($r);
    }
    public static function replaceSanitizedDataContinent($dresult, $item)
    {
        $r = str_replace(['@@continent@@'], [$dresult['header']], $item);
        return Content::sanitizeData($r);
    }
    public static function sanitizeData($item)
    {
    	return stripslashes(str_replace(['\r\n', '\r', '\n', '\t', '  '], ' ', $item));
    }
    public static function replaceDataTrip($dresult, $dresult2, $item, $airport_flag, $airport_flag2)
    {
        return stripslashes(str_replace(['@@site@@', '@@country@@', '@@country_alt@@', '@@country_code@@', '@@city@@', '@@city_alt@@', '@@city_code@@', '@@airport@@', '@@station@@', '@@airport_code@@', '@@lat@@', '@@lng@@', '@@carrier@@', '@@carrier_code@@',
            '@@from@@', '@@to@@'],
            ['avia.md',
                (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''),
                (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''),
                (isset($dresult['country_ansi2']) ? $dresult['country_ansi2'] : ''),
                $dresult['city_caption'], $dresult['city_caption'], $dresult['city_code'],
                $dresult['airport_caption'], $dresult['airport_caption'], $dresult['airport_code'],
                $dresult['Latitude'], $dresult['Longitude'],
                (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''),
                (isset($dresult['country_ansi2']) ? $dresult['country_ansi2'] : ''),
                ($airport_flag) ? $dresult['airport_caption'].' ('.$dresult['city_caption'].')' : $dresult['city_caption'],
                ($airport_flag2) ? $dresult2['airport_caption'].' ('.$dresult2['city_caption'].')' : $dresult2['city_caption']
            ],
            $item));
    }
    public static function replaceSanitizedDataTrip($dresult, $dresult2, $item, $airport_flag, $airport_flag2)
    {
        $r = str_replace(['@@site@@', '@@country@@', '@@country_alt@@', '@@country_code@@', '@@city@@', '@@city_alt@@', '@@city_code@@', '@@airport@@', '@@station@@', '@@airport_code@@', '@@lat@@', '@@lng@@', '@@carrier@@', '@@carrier_code@@',
            '@@from@@', '@@to@@'],
            ['avia.md',
                (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''),
                (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''),
                (isset($dresult['country_ansi2']) ? $dresult['country_ansi2'] : ''),
                $dresult['city_caption'], $dresult['city_caption'], $dresult['city_code'],
                $dresult['airport_caption'], $dresult['airport_caption'], $dresult['airport_code'],
                $dresult['Latitude'], $dresult['Longitude'],
                (isset($dresult['country_caption']) ? $dresult['country_caption'] : ''),
                (isset($dresult['country_ansi2']) ? $dresult['country_ansi2'] : ''),
                ($airport_flag) ? $dresult['airport_caption'].' ('.$dresult['city_caption'].')' : $dresult['city_caption'],
                ($airport_flag2) ? $dresult2['airport_caption'].' ('.$dresult2['city_caption'].')' : $dresult2['city_caption']
            ],
            $item);
        return Content::sanitizeData($r);
    }

    public static function contentModify($dresult, $result, $result2 = [])
    {
//echo "<pre>"; var_dump($result, $dresult, $result2); echo "</pre>";exit;

        $result['dynamic'] = $dresult;
        if (isset($result2['body']) && strlen($result2['body']) > 20)
            $result['body'] = Content::replaceData($dresult, $result2['body']);
        else
            $result['body'] = ( (isset($result2['body'])) && ($result2['body'] != '') ) ? $result2['body'] : Content::replaceSanitizedData($dresult, $result['body']);

        isset($result['special_content']) and $result['special_content'] = Content::replaceData($dresult, $result['special_content']);
//        isset($result['header']) and $result['header'] = Content::replaceSanitizedData($dresult, $result['header']);
        if (isset($result['header']))
        {
            $result['header'] = ( (isset($result2['header'])) && ($result2['header'] != '') ) ? Content::replaceSanitizedData($dresult, $result2['header']) : Content::replaceSanitizedData($dresult, $result['header']);
        }
        isset($result['title']) and $result['title'] = Content::replaceSanitizedData($dresult, $result['title']);
//        isset($result['seo_title']) and $result['seo_title'] = Content::replaceSanitizedData($dresult, $result['seo_title']);
        if (isset($result['seo_title']))
        {
            $result['seo_title'] = ( (isset($result2['title'])) && ($result2['title'] != '') ) ? Content::replaceSanitizedData($dresult, $result2['title']) : Content::replaceSanitizedData($dresult, $result['seo_title']);
        }
//        isset($result['seo_keywords']) and $result['seo_keywords'] = Content::replaceSanitizedData($dresult, $result['seo_keywords']);
        if (isset($result['seo_keywords']))
        {
            $result['seo_keywords'] = ( (isset($result2['keywords'])) && ($result2['keywords'] != '') ) ? Content::replaceSanitizedData($dresult, $result2['keywords']) : Content::replaceSanitizedData($dresult, $result['seo_keywords']);
        }
//        isset($result['seo_description']) and $result['seo_description'] = Content::replaceSanitizedData($dresult, $result['seo_description']);
        if (isset($result['seo_description']))
        {
            $result['seo_description'] = ( (isset($result2['description'])) && ($result2['description'] != '') ) ? Content::replaceSanitizedData($dresult, $result2['description']) : Content::replaceSanitizedData($dresult, $result['seo_description']);
        }
        if (!isset($result['title'])) $result['title'] = $result['header'];
        $result['city_code'] = $dresult['city_code'];
        $result['airport_caption'] = $dresult['airport_caption'];
        if (!isset($result['seo_title'])) $result['seo_title'] = Content::replaceSanitizedData($dresult, $result['title']);
        if (!isset($result['seo_keywords'])) $result['seo_keywords'] = Content::replaceSanitizedData($dresult, $result['keywords']);
        if (!isset($result['seo_description'])) $result['seo_description'] = Content::replaceSanitizedData($dresult, $result['description']);

        if (isset($result2['image']) && !isset($result['image'])) $result['image'] = $result2['image'];
        else if (!isset($result2['image'])) $result['image'] = '';
//        echo "<pre>"; var_dump($result['image'], $result2['image']); echo "</pre>";

        return $result;
    }
    public static function contentModifyTrip($dresult, $dresult2, $result, $airport_flag, $airport_flag2)
    {
        $result['dynamic'] = $dresult;
        $result['dynamic2'] = $dresult2;
        $result['body'] = Content::replaceDataTrip($dresult, $dresult2, $result['body'], $airport_flag, $airport_flag2);
        isset($result['special_content']) and $result['special_content'] = Content::replaceDataTrip($dresult, $dresult2, $result['special_content'], $airport_flag, $airport_flag2);
        isset($result['header']) and $result['header'] = Content::replaceSanitizedDataTrip($dresult, $dresult2, $result['header'], $airport_flag, $airport_flag2);
        isset($result['title']) and $result['title'] = Content::replaceSanitizedDataTrip($dresult, $dresult2, $result['title'], $airport_flag, $airport_flag2);
        isset($result['seo_title']) and $result['seo_title'] = Content::replaceSanitizedDataTrip($dresult, $dresult2, $result['seo_title'], $airport_flag, $airport_flag2);
        isset($result['seo_keywords']) and $result['seo_keywords'] = Content::replaceSanitizedDataTrip($dresult, $dresult2, $result['seo_keywords'], $airport_flag, $airport_flag2);
        isset($result['seo_description']) and $result['seo_description'] = Content::replaceSanitizedDataTrip($dresult, $dresult2, $result['seo_description'], $airport_flag, $airport_flag2);

        if (!isset($result['title'])) $result['title'] = $result['header'];
        $result['city_code'] = $dresult['city_code'];
        $result['airport_caption'] = $dresult['airport_caption'];
        if (!isset($result['seo_title'])) $result['seo_title'] = Content::replaceSanitizedDataTrip($dresult, $result['title'], $airport_flag, $airport_flag2);
        if (!isset($result['seo_keywords'])) $result['seo_keywords'] = Content::replaceSanitizedDataTrip($dresult, $result['keywords'], $airport_flag, $airport_flag2);
        if (!isset($result['seo_description'])) $result['seo_description'] = Content::replaceSanitizedDataTrip($dresult, $result['description'], $airport_flag, $airport_flag2);
        if (!isset($result['image'])) $result['image'] = '';

        return $result;
    }
/*
    public static function dataByView($view, $data)
    {
        if($view == 'news_line'){
            $data['news'] = NewsLine::allNews();
            $data['table'] = 'ut_news_line/';
        } else if($view == 'contact'){
            $data['contact'] = Filial::find()->where(['isPublic' => 1])->orderBy('OrderIndex')->all();
        }else if($view == 'offers_line'){
            $data['offers'] = Offer::find()
            ->select(['Header_From', 'Header_To', 'Price', 'Link', 'isHot', 'ImageURL', 'ut_offer.CountryID'])
            ->where(['ut_offer.isPublic' => 1])
            ->joinWith('country')
            ->limit(3)
            ->all();
        }

        return $data;
    }
*/

    public static function searchAirport($post_request)
    {
        $lang = Yii::$app->language;

        $query = "SELECT
                ut_new_aeroport.dst_code AS ANSI3,
                ut_new_city.city_code AS city_code,
                ut_new_city.city_name AS City,
                json_get(ut_new_aeroport.dst_txt, '$lang') AS Airport,
                json_get(ut_country.CountryCaption, '$lang') AS Country,
                ut_new_city.country_code,
                ut_new_city.isTop AS isTopC,
                ut_new_aeroport.isTop AS isTopA

                FROM
                ut_new_aeroport,
                ut_new_city,
                ut_country

                WHERE
                (ut_new_aeroport.city_code = ut_new_city.city_code) AND
                (ut_new_city.country_code = ut_country.ANSI2) AND

                (
                ut_new_city.city_code = '%s' OR
                ut_new_aeroport.dst_code = '%s' OR
                ut_new_city.city_name LIKE '%\"ru\":\"%s%\"%' OR
                ut_new_city.city_name LIKE '%\"ro\":\"%s%\"%' OR
                ut_new_city.city_name LIKE '%\"en\":\"%s%\"%' OR
                ut_new_city.sinonim LIKE '%s%' OR ut_new_city.sinonim LIKE '%,%s%' OR
                ut_new_aeroport.dst_txt LIKE '%\"ru\":\"%s%\"%' OR
                ut_new_aeroport.dst_txt LIKE '%\"ro\":\"%s%\"%' OR
                ut_new_aeroport.dst_txt LIKE '%\"en\":\"%s%\"%'
                )
                AND (ut_new_aeroport.port_type <> '')
                AND (port_type IN ('large_airport','medium_airport','small_airport')) /*AND (ut_new_aeroport.port_type <> 'closed')*/
                AND (ut_new_aeroport.isActive = 1)

                ORDER BY

                ut_new_city.isTop DESC,
                IF(ut_new_city.city_name LIKE '%\"ru\":\"%s%\"%' OR ut_new_city.city_name LIKE '%\"ro\":\"%s%\"%' OR ut_new_city.city_name LIKE '%\"en\":\"%s%\"%', 1, 0) DESC,
                IF(ut_new_city.sinonim LIKE '%s%' OR ut_new_city.sinonim LIKE '%,%s%', 0, 1) DESC,
                City,
                ut_new_aeroport.isTop DESC,
                IF(ut_new_aeroport.dst_code = '%s', 1, 0) DESC,
                (IF(ut_new_aeroport.dst_txt LIKE '%\"ru\":\"%s%\"%', 2, 1) OR IF(ut_new_aeroport.dst_txt LIKE '%\"ro\":\"%s%\"%', 2, 1) OR IF(ut_new_aeroport.dst_txt LIKE '%\"en\":\"%s%\"%', 2, 1) ) DESC,
                Airport
";
        $query = str_replace('%s', $post_request['search_val'], $query);
        return Yii::$app->db->createCommand($query)->queryAll();
    }

    public static function getAirportsByCityCode($code)
    {
        $lang = Yii::$app->language;
        $query = "SELECT dst_code FROM ut_new_aeroport
        WHERE (ut_new_aeroport.city_code = '{$code}')
        AND (port_type IN ('large_airport','medium_airport','small_airport')) /*AND (ut_new_aeroport.port_type <> 'closed')*/
/*        AND (NOT ISNULL(port_type))*/
/*        AND (IFNULL(port_type, '') != '')*/
        AND (isActive = 1)";
        $ret = Yii::$app->db->createCommand($query)->queryAll();
        $r = [];
        foreach ($ret as $k=>$v) array_push($r, $v['dst_code']);
        return $r;
    }


    public static function getCityPairs($r = false, $lang = 'ro', $priority = 0.0)
    {
        $ret = Yii::$app->db->createCommand("SELECT ut_feed_pairs.ImageURL_desktop, ut_feed_pairs.ImageURL_mobile FROM ut_feed_pairs WHERE (ut_feed_pairs.city_from = '') AND (ut_feed_pairs.city_to = '')")->queryOne();

        if (!$r) {
            $empty_pattern = $ret['ImageURL_desktop'];
        $query = "SELECT '$lang' AS lang,
  if (json_get(T1.body, '$lang') != '', json_get(T1.body, '$lang'), json_get(T0.body, '$lang')) AS caption_searchresults,
/*  if (json_get(T2.body, '$lang') != '', json_get(T2.body, '$lang'), json_get(T0.body, '$lang')) AS caption_offerdetail,*/
/*  if (json_get(T3.body, '$lang') != '', json_get(T3.body, '$lang'), json_get(T0.body, '$lang')) AS caption_cart,*/
  if (json_get(T4.body, '$lang') != '', json_get(T4.body, '$lang'), json_get(T0.body, '$lang')) AS caption_purchase,
  if (json_get(T5.body, '$lang') != '', json_get(T5.body, '$lang'), json_get(T0.body, '$lang')) AS caption_home,
  if (json_get(T6.body, '$lang') != '', json_get(T6.body, '$lang'), json_get(T0.body, '$lang')) AS caption_other,
/*  if (json_get(T7.body, '$lang') != '', json_get(T7.body, '$lang'), json_get(T0.body, '$lang')) AS caption_cancel,*/
  IF(ut_feed_pairs.ImageURL_desktop = '', ut_feed_pairs.ImageURL_mobile, ut_feed_pairs.ImageURL_desktop) AS ImageURL,
    ut_feed_pairs.city_from AS code_from,
  ut_feed_pairs.city_to AS code_to,
  json_get(C1.city_name, '$lang') AS city_from,
  json_get(C2.city_name, '$lang') AS city_to,
  ut_feed_pairs.flight_price AS avg_price,
  ut_feed_pairs.flight_sale_price AS min_price,
  json_get(ut_feed_pairs.formatted_sale_price, '$lang') AS formatted_price,
/*  IF (json_get(ut_feed_pairs.formatted_sale_price, '$lang') <> '', json_get(ut_feed_pairs.formatted_sale_price, '$lang'), CONCAT(ut_feed_pairs.flight_sale_price, ' Euro')) AS formatted_price, */
  show_condition AS show_condition
FROM
  ut_feed_pairs
  LEFT OUTER JOIN ut_new_city C1 ON (ut_feed_pairs.city_from = C1.city_code)
  LEFT OUTER JOIN ut_new_city C2 ON (ut_feed_pairs.city_to = C2.city_code)
  LEFT OUTER JOIN ut_feed_terms T1 ON (T1.alias = ut_feed_pairs.caption_searchresults)
/*  LEFT OUTER JOIN ut_feed_terms T2 ON (T2.alias = ut_feed_pairs.caption_offerdetail)*/
/*  LEFT OUTER JOIN ut_feed_terms T3 ON (T3.alias = ut_feed_pairs.caption_cart)*/
  LEFT OUTER JOIN ut_feed_terms T4 ON (T4.alias = ut_feed_pairs.caption_purchase)
  LEFT OUTER JOIN ut_feed_terms T5 ON (T5.alias = ut_feed_pairs.caption_home)
  LEFT OUTER JOIN ut_feed_terms T6 ON (T6.alias = ut_feed_pairs.caption_other)
/*  LEFT OUTER JOIN ut_feed_terms T7 ON (T7.alias = ut_feed_pairs.caption_cancel)*/
  LEFT OUTER JOIN ut_feed_terms T0 ON (T0.alias = ut_feed_pairs.caption_default)
WHERE (ut_feed_pairs.city_from <> '') AND (ut_feed_pairs.city_to <> '')
AND ( (ut_feed_pairs.flight_sale_price > 0.0) OR (json_get(ut_feed_pairs.formatted_sale_price, '$lang') <> '') )
AND (C1.priority >= {$priority}) AND (C2.priority >= {$priority})
";
        }
        else
        {
            $empty_pattern = $ret['ImageURL_mobile'];
            $query = "SELECT '$lang' AS lang,
  if (json_get(T1.body, '$lang') != '', json_get(T1.body, '$lang'), json_get(T0.body, '$lang')) AS caption_searchresults,
/*  if (json_get(T2.body, '$lang') != '', json_get(T2.body, '$lang'), json_get(T0.body, '$lang')) AS caption_offerdetail,*/
/*  if (json_get(T3.body, '$lang') != '', json_get(T3.body, '$lang'), json_get(T0.body, '$lang')) AS caption_cart,*/
  if (json_get(T4.body, '$lang') != '', json_get(T4.body, '$lang'), json_get(T0.body, '$lang')) AS caption_purchase,
  if (json_get(T5.body, '$lang') != '', json_get(T5.body, '$lang'), json_get(T0.body, '$lang')) AS caption_home,
  if (json_get(T6.body, '$lang') != '', json_get(T6.body, '$lang'), json_get(T0.body, '$lang')) AS caption_other,
/*  if (json_get(T7.body, '$lang') != '', json_get(T7.body, '$lang'), json_get(T0.body, '$lang')) AS caption_cancel,*/
  IF(ut_feed_pairs.ImageURL_mobile = '', ut_feed_pairs.ImageURL_desktop, ut_feed_pairs.ImageURL_mobile) AS ImageURL,
  ut_feed_pairs.city_to AS code_from,
  ut_feed_pairs.city_from AS code_to,
  json_get(C2.city_name, '$lang') AS city_from,
  json_get(C1.city_name, '$lang') AS city_to,
  ut_feed_pairs.flight_price_ret AS avg_price,
  ut_feed_pairs.flight_sale_price_ret AS min_price,
  json_get(ut_feed_pairs.formatted_sale_price_ret, '$lang') AS formatted_price,
/*  IF (json_get(ut_feed_pairs.formatted_sale_price_ret, '$lang') <> '', json_get(ut_feed_pairs.formatted_sale_price_ret, '$lang'), CONCAT(ut_feed_pairs.flight_sale_price_ret, ' Euro')) AS formatted_price */
  show_condition_ret AS show_condition
FROM
  ut_feed_pairs
  LEFT OUTER JOIN ut_new_city C1 ON (ut_feed_pairs.city_from = C1.city_code)
  LEFT OUTER JOIN ut_new_city C2 ON (ut_feed_pairs.city_to = C2.city_code)
  LEFT OUTER JOIN ut_feed_terms T1 ON (T1.alias = ut_feed_pairs.caption_searchresults)
/*  LEFT OUTER JOIN ut_feed_terms T2 ON (T2.alias = ut_feed_pairs.caption_offerdetail)*/
/*  LEFT OUTER JOIN ut_feed_terms T3 ON (T3.alias = ut_feed_pairs.caption_cart)*/
  LEFT OUTER JOIN ut_feed_terms T4 ON (T4.alias = ut_feed_pairs.caption_purchase)
  LEFT OUTER JOIN ut_feed_terms T5 ON (T5.alias = ut_feed_pairs.caption_home)
  LEFT OUTER JOIN ut_feed_terms T6 ON (T6.alias = ut_feed_pairs.caption_other)
/*  LEFT OUTER JOIN ut_feed_terms T7 ON (T7.alias = ut_feed_pairs.caption_cancel)*/
  LEFT OUTER JOIN ut_feed_terms T0 ON (T0.alias = ut_feed_pairs.caption_default_ret)
WHERE (ut_feed_pairs.city_from <> '') AND (ut_feed_pairs.city_to <> '')
AND ( (ut_feed_pairs.flight_sale_price_ret > 0.0) OR (json_get(ut_feed_pairs.formatted_sale_price_ret, '$lang') <> '') )
AND (C1.priority >= {$priority}) AND (C2.priority >= {$priority})
";
        }
        $ret = Yii::$app->db->createCommand($query)->queryAll();
        $ret[0]['empty_pattern'] = $empty_pattern;

        return $ret;
    }

    public static function getCityPairsRaw($priority = 0.0)
    {
//        $query = "SELECT city_from AS code_from, city_to AS code_to, window_minus, window_plus FROM ut_feed_pairs";
        $query = "SELECT city_from AS code_from, city_to AS code_to, window_minus, window_plus FROM ut_new_city
            RIGHT OUTER JOIN ut_feed_pairs ON (ut_feed_pairs.city_to = ut_new_city.city_code)
            WHERE (city_to != '') AND (ut_new_city.priority >= {$priority}) ORDER BY city_to";

        $ret = Yii::$app->db->createCommand($query)->queryAll();
        return $ret;
    }

    public static function formatCityPairs($res)
    {
        $ret = '';
        foreach ($res as $val)
        {
            $ret .=  '"'.$val['lang'].'";';
            $ret .=  '"searchresults";';
            $ret .=  '"'.$val['code_to'].'";';
            $ret .=  '"'.$val['code_from'].'";';
            $ret .=  '"'.$val['caption'].' '.$val['city_from'].' - '.$val['city_to'].'";';
            $ret .=  '"https://avia.md/ru/spo/'.strtolower($val['code_from']).'/'.strtolower($val['code_to']).'/'.Yii::$app->params['feed_to_interval'].'/'.Yii::$app->params['feed_from_interval'].'/";';
            $ret .=  '"https://cdn.avia.md/?mode=asis&c=1&mime=png&url=https://manager.avia.md/images/ut_feed_pairs/'.$val['ImageURL'].'";';
            $ret .=  '"'.$val['city_to'].'";';
            $ret .=  '"'.$val['city_from'].'";';
            $ret .=  '"EUR";';
            $ret .=  '"'.$val['price'].'";';
            $ret .=  '"'.$val['formatted_price'].'";';
            $ret .= "\r\n";
        }
        return $ret;
    }


    public static function formatArrayCityPairs($res, $pagetype = 'other')
    {
        $ret = []; $i = 0;
        foreach ($res as $val)
        {
            if ( ($val['min_price'] == 0) && ($val['avg_price'] == 0) && ($val['formatted_price'] == '') ) break;

            $min_price = ($val['min_price'] != '') ? $val['min_price'] : null ;
            $avg_price = ($val['avg_price'] != '') ? $val['avg_price'] : null ;
            $formatted_price = ($val['formatted_price'] != '') ? $val['formatted_price'] : '-' ;
            switch ($val['show_condition'])
            {
                default:
                case '1':
                    $price = ($min_price) ? number_format(round($min_price, 0, PHP_ROUND_HALF_UP), 0, '.', ' ') : $formatted_price;
                break;
                case '2':
                    $price = ($avg_price) ? number_format(round($avg_price, 0, PHP_ROUND_HALF_UP), 0, '.', ' ') : $formatted_price;
                break;
                case '3':
                    $price = $formatted_price;
                break;
            }
            $min_price = ($min_price) ? number_format(round($min_price, 0, PHP_ROUND_HALF_UP), 0, '.', ' ') : '0';
            $avg_price = ($avg_price) ? number_format(round($avg_price, 0, PHP_ROUND_HALF_UP), 0, '.', ' ') : '0';

            $code_to = ($val['code_to'] != '') ? $val['code_to'] : '-' ;
            $code_from = ($val['code_from'] != '') ? $val['code_from'] : '-' ;
            $city_to = ($val['city_to'] != '') ? $val['city_to'] : '-' ;
            $city_from = ($val['city_from'] != '') ? $val['city_from'] : '-' ;

            $banner_price = ($val['formatted_price'] != '') ? $val['formatted_price'] : '0' ;
            $caption = ($val['caption_'.$pagetype] != '') ? $val['caption_'.$pagetype] : '-' ;

            $caption = str_replace([
                '@@dest_code@@', '@@origin_code@@', '@@dest@@', '@@origin@@', '@@price@@'
            ], [
                $code_to, $code_from, $city_to, $city_from, $price
            ], $caption);
/*
            $ret[$i] = [
                'A' => $val['lang'],
                'B' => $pagetype,
                'C' => $code_to,
                'D' => $code_from,
                'E' => $caption,
                'F' => 'https://avia.md/'.$val['lang'].'/spo/'.strtolower($val['code_from']).'/'.strtolower($val['code_to']).'/15/3/',
                'G' => 'https://cdn.avia.md/?mode=asis&c=1&mime=png&url=https://manager.avia.md/images/ut_feed_pairs/'.$val['ImageURL_desktop'],
                'H' => 'https://cdn.avia.md/?mode=asis&c=1&mime=png&url=https://manager.avia.md/images/ut_feed_pairs/'.$val['ImageURL_mobile'],
                'I' => $city_to,
                'J' => $city_from,
                'K' => 'EUR',
                'L' => $avg_price,
                'M' => $min_price,
                'N' => $price
            ];
*/
            $ret[$i] = [
                'A' => $code_to,
                'B' => $code_from,
                'C' => $caption,
                'D' => 'https://avia.md/'.$val['lang'].'/spo/'.strtolower($val['code_from']).'/'.strtolower($val['code_to']).'/'.Yii::$app->params['feed_to_interval'].'/'.Yii::$app->params['feed_from_interval'].'/',
                'E' => 'https://cdn.avia.md/?mode=asis&c=1&mime=png&url=https://manager.avia.md/images/ut_feed_pairs/'.(($val['ImageURL'] != '') ? $val['ImageURL'] : $res[0]['empty_pattern']),
                'F' => $city_to,
                'G' => $city_from,
                'H' => $avg_price.' EUR',
                'I' => $min_price.' EUR',
                'J' => $price.' EUR'
            ];

            $i++;
        }
        return $ret;
    }

    public static function getFeedList($priority = 0.0)
    {
        $query = "SELECT ut_feed_pairs.city_to FROM ut_new_city
            RIGHT OUTER JOIN ut_feed_pairs ON (ut_feed_pairs.city_to = ut_new_city.city_code)
            WHERE (city_to != '') AND (ut_new_city.priority >= {$priority}) ORDER BY city_to";
        $ret = Yii::$app->db->createCommand($query)->queryAll();
        return $ret;
    }

}
