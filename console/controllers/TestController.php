<?php

namespace yiiImage\console\controllers;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Тестовый контроллер для изображений
 */
class TestController extends Controller {

	/**
	 * Загрузка изображения из файла.
	 *
	 * @param string $filePath Путь к файлу изображения
	 * @param string $title    Название изображения
	 *
	 * @return string Идентификатор изображения или сообщение об ошибке
	 */
	public function actionUploadFromFile($filePath, $title) {
		$this->stdout('Загружаем изображение' . PHP_EOL);
		$result = Yii::$app->moduleManager->modules->image->loadImageByFile($filePath, $title);
		if ($result === null) {
			$this->stdout('Не удалось загрузить изображение' . PHP_EOL, Console::FG_RED);
		}
		else {
			$this->stdout('Изображение загружено успешно, id = ' . $result . PHP_EOL, Console::FG_GREEN);
		}
	}

	/**
	 * Загрузка изображения из файла.
	 *
	 * @param string $fileUrl Url изображения
	 * @param string $title   Название изображения
	 *
	 * @return string Идентификатор изображения или сообщение об ошибке
	 */
	public function actionUploadFromUrl($fileUrl, $title) {
		$this->stdout('Загружаем изображение' . PHP_EOL);
		$result = Yii::$app->moduleManager->modules->image->loadImageByUrl($fileUrl, $title);
		if ($result === null) {
			$this->stdout('Не удалось загрузить изображение' . PHP_EOL, Console::FG_RED);
		}
		else {
			$this->stdout('Изображение загружено успешно, id = ' . $result . PHP_EOL, Console::FG_GREEN);
		}
	}

	/**
	 * Получение URL тамбов загруженного изображения.
	 *
	 * @param string $imageId Идентификатор изображения
	 *
	 * @return string URL-ы изображений или сообщение об ошибке
	 */
	public function actionGetUrl($imageId) {
		$imageModule = Yii::$app->moduleManager->modules->image;
		foreach ($imageModule->thumbsFormats as $formatId => $formatParams) {
			$url = $imageModule->getFrontImageUrl($imageId, $formatId);
			if ($url === null) {
				$this->stdout('Не удалось получить URL изображения' . PHP_EOL, Console::FG_RED);
			}
			else {
				$this->stdout('URL изображения: ' . $url . PHP_EOL, Console::FG_GREEN);
			}
		}
	}
}