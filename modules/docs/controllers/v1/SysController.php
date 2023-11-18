<?php

namespace openecontmd\docs_api\modules\docs\controllers\v1;

use yii\rest\Controller;
use openecontmd\docs_api\models\Helper;
use OpenApi\Annotations as OA;

class SysController extends Controller
{
	public function __construct($id, $module, $config = [])
	{
		parent::__construct($id, $module, $config);
		return true;
	}

/**
 * @OA\Post(
 *     path="/docs/v1/sys",
 *     tags={"docs"},
 *     @OA\Response(
 *         response="200",
 *         description="Available Actions",
 *         @OA\JsonContent(
 *              @OA\Schema(ref="#/components/schemas/result"),
 *              @OA\Examples(example="result", value={
 "status": "OK",
 "data": {
 "available_actions": {"check", "info", "spend"},
 "controller": "sys",
 "entry_point": "https://api.open.e-cont.md/docs/v1/sys/{action}"
 }
 }, summary="An result object."),
 *          )
 *     ),
 *
 *     @OA\Response(
 *         response="400",
 *         description="Bad Request",
 *         @OA\JsonContent(
 *              @OA\Schema(ref="#/components/schemas/result"),
 *              @OA\Examples(example="result", value={
 "status": "FAIL",
 "data": {
 "name": "Bad Request",
 "message": {"session_id":"ID сессии cannot be blank."},
 "code": 0,
 "status": 400,
 "type": "yii\web\BadRequestHttpException"
 }
 }, summary="An result object."),
 *          )
 *     )
 * )
 */
	public function actionIndex()
	{
	    return [
	        'available_actions' => ['check', 'info', 'spend'],
	        'controller' => 'sys',
	        'entry_point' => 'https://api.open.e-cont.md/docs/v1/sys/{action}',
	    ];
	}

/**
 * @OA\Post(
 *     path="/docs/v1/sys/check",
 *     tags={"docs"},
 *     @OA\Response(
 *         response="200",
 *         description="Check Availability",
 *     )
 * )
 */
	public function actionCheck()
	{
	    $response = [
	        'endpoint' => 'docs',
	        'controller' => 'sys',
	        'version' => 'Version-1.0',
	        'status' => 'In work',
	        'availability' => '100%',
	        'entry_point' => 'https://api.open.e-cont.md/docs/v1/sys/{action}',
	    ];
	    return $response;
	}

/**
 * @OA\Post(
 *     path="/docs/v1/sys/info",
 *     tags={"docs"},
 *     @OA\Response(
 *         response="200",
 *         description="API Branch Info",
 *     )
 * )
 */
	public function actionInfo()
	{
	    $response = [
	        'endpoint' => 'docs',
	        'controller' => 'sys',
	        'caption' => 'API for Docs Invoice Repository',
	        'version' => 'Version-1.0',
	    ];
	    return $response;
	}

/**
 * @OA\Post(
 *     path="/docs/v1/sys/spend",
 *     tags={"docs"},
 *     @OA\Response(
 *         response="200",
 *         description="API Spend Info",
 *     )
 * )
 */
	public function actionSpend()
	{
	    $response = [
	        'endpoint' => 'docs',
	        'controller' => 'sys',
	        'account_records' => [
	            'departments' => 3,
	            'invoices' => 122,
	            'customers' => 7,
	        ],
	    ];
	    return $response;
	}

/**
 * @OA\Post(
 *     path="/docs/v1/sys/init",
 *     tags={"docs"},
 *     @OA\Response(
 *         response="200",
 *         description="GUID",
 *     )
 * )
 */
	public function actionInit()
	{
	    $new_id = Helper::newid();
	    $response = [
	        'endpoint' => 'docs',
	        'controller' => 'sys',
	        'action' => 'init',
	        'guid' => $new_id,
	        'status' => 'initiate'
	    ];
	    return $response;
	}

}