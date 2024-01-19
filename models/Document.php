<?php

namespace app\models;

use Yii;

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


}
