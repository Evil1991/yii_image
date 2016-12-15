<?php

namespace yiiImage\console\controllers;

use common\modules\game\models\RefGameTblImageLink;
use common\modules\game\models\RefProductTblImageLink;
use yiiImage\models\RefImage;
use common\modules\sale\models\RefSaleWalletType;
use Yii;
use yii\console\Controller;

class ImageClearController extends Controller {

	/**
	 * Очистка от неиспользуемых изображений.
	 */
	public function actionClearUnusedImages() {
		$this->stdout('Поиск неиспользуемых изображений... ');

		$result = Yii::$app->db->createCommand('SELECT i.id
			FROM ' . RefImage::tableName() . ' as i
			LEFT JOIN ' . RefGameTblImageLink::tableName() . ' gi on gi.image_id = i.id
			LEFT JOIN ' . RefProductTblImageLink::tableName() . ' pi on pi.image_id = i.id
			LEFT JOIN ' . RefSaleWalletType::tableName() . ' wt on wt.image_id = i.id
			WHERE gi.game_id IS NULL AND pi.product_id IS NULL AND wt.id IS NULL 
		')->queryColumn();

		$this->stdout('найдено ' . count($result) . PHP_EOL);

		foreach ($result as $imageId) {
			$this->stdout('Удаляем изображение id = ' . $imageId . '... ');

			if (Yii::$app->moduleManager->modules->image->deleteImageById($imageId)) {
				$this->stdout('успешно');
			}
			else {
				$this->stdout('ОШИБКА');
			}

			$this->stdout(PHP_EOL);

		}
	}

}