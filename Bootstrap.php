<?php

namespace openecontmd\docs_api;

use yii\base\BootstrapInterface;
use Yii;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        /*
         * Регистрация модуля в приложении
         * (вместо указания в файле config/web.php
         */
        $app->setModule('docs', 'openecontmd\docs_api\modules\docs\Module');

//	?????????????????????????????????????????
        $app->getUrlManager()->addRules([

            '<controller:\w+>' => '<controller>/index',
            '<controller:\w+>/<id:\d+>'=>'<controller>/view',
            '<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
            '<controller:\w+>/<action:\w+>'=>'<controller>/<action>'

        ], false);



    }


}
