<?php
// analytics.avia.md@gmail.com


namespace app\modules\content\controllers\v1;

use Yii;
//use yii\base\Exception;
use yii\rest\Controller;
use app\models\Document;
//use linslin\yii2\curl\Curl;
//use yii\web\HttpException;
//use app\modules\common\sms\OrangeHTTP;
//use app\modules\common\loyalty\Loyalty;
//use app\models\Terms;

class DocsController extends Controller
{

	public $response;

	public function __construct($id, $module, $config = [])
	{
		parent::__construct($id, $module, $config);
		return true;
	}

	public function actionIndex()
	{
	    //return [];

	    //$request = Yii::$app->request->post('request');
	    $request = Yii::$app->request->get();

	    /**
	    $log = "\n------------------------\n";
	    $log .= date("Y.m.d G:i:s")."\n";
	    $log .= "Post: ".print_r(Yii::$app->request->post(), 1)."\n";
	    $log .= "Get: ".print_r(Yii::$app->request->get(), 1)."\n";
	    $log .= "Request: ".print_r($request, 1)."\n";
	    //	     $log .= "order: ".print_r($order, 1)."\n";
	    //	     $log .= "inv: ".print_r($inv, 1)."\n";
	    $log .= "\n------------------------\n";
	    //file_put_contents("./../../../../runtime/logs/put_invoice_wc.log", $log, FILE_APPEND);
	    //file_put_contents("put_invoice_wc.log", $log, FILE_APPEND);
	    file_put_contents(__DIR__."/test_index.log", $log, FILE_APPEND);
	    /**/

	    Yii::$app->response->format = 'json';
		return [$request, $lang, $alias];
	}

	public function actionPage($lang = 'ro', $alias = '')
	{
	    //return [];

	    //$request = Yii::$app->request->post('request');
	    $request = Yii::$app->request->get();

	    $result = Document::getContentPage($alias, $lang);

	    /**
	     $log = "\n------------------------\n";
	     $log .= date("Y.m.d G:i:s")."\n";
	     $log .= "Post: ".print_r(Yii::$app->request->post(), 1)."\n";
	     $log .= "Get: ".print_r(Yii::$app->request->get(), 1)."\n";
	     $log .= "Request: ".print_r($request, 1)."\n";
	     //	     $log .= "order: ".print_r($order, 1)."\n";
	     //	     $log .= "inv: ".print_r($inv, 1)."\n";
	     $log .= "\n------------------------\n";
	     //file_put_contents("./../../../../runtime/logs/put_invoice_wc.log", $log, FILE_APPEND);
	     //file_put_contents("put_invoice_wc.log", $log, FILE_APPEND);
	     file_put_contents(__DIR__."/test_index.log", $log, FILE_APPEND);
	     /**/

	    Yii::$app->response->format = 'json';
	    return ['lang' => $lang, 'alias' => $alias, 'result' => $result];
	}

//  	https://api.diginet.md/facturare/v1/test/google?key=693924ca486e72443eaa16553f33103c
//  	http://api.newlife.tst/facturare/v1/test/google?key=693924ca486e72443eaa16553f33103c  #  616
//      205099bf6775e3bf72a9efacf0880324  #  637



	public function actionAuth()
	{
//	    var_dump(Yii::$app->request->get()); exit;
	    $request_data = Yii::$app->request->get();
/*
	    $data = [
	        "get" => Yii::$app->request->get(),
//	        "post" => Yii::$app->request->post()
	    ];
*/
	    /**
	     $log = "\n------------------------\n";
	     $log .= date("Y.m.d G:i:s")."\n";
	     $log .= "Data: ".print_r($data, 1)."\n";
	     //	     $log .= "order: ".print_r($order, 1)."\n";
	     //	     $log .= "inv: ".print_r($inv, 1)."\n";
	     $log .= "\n------------------------\n";
	     //file_put_contents("./../../../../runtime/logs/put_invoice_wc.log", $log, FILE_APPEND);
	     //file_put_contents("put_invoice_wc.log", $log, FILE_APPEND);
	     file_put_contents(__DIR__."/test_auth.log", $log, FILE_APPEND);
	     /**/
	     Yii::$app->response->format = 'json';
	     return  $request_data; //$inv; //$csv;
	}

}