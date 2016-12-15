<?php

namespace yiiImage\console\controllers;

use yiiCustom\console\components\MigrationProvider;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;
use yiiImage\models\RefImage;

/**
 * Инициализация модуля изображений.
 */
class InitController extends Controller {

	/**
	 * Инициализация.
	 */
	public function actionInit() {
		foreach ($this->getMigrationsPath() as $path => $migrationName) {
			$this->stdout('Выполняем миграцию: ' . $migrationName . PHP_EOL);
			MigrationProvider::getMigration($path, $migrationName)->up();
		}

		$this->stdout('Инициализация успешно выполнена' . PHP_EOL, Console::FG_GREEN);
	}

	/**
	 * Удаление.
	 */
	public function actionRemove() {
		foreach ($this->getMigrationsPath() as $path => $migrationName) {
			$this->stdout('Откатываем миграцию: ' . $migrationName . PHP_EOL);
			MigrationProvider::getMigration($path, $migrationName)->down();
		}

		$this->stdout('Удаляем все изображения' . PHP_EOL);

		$imagesIds = RefImage::find()
			->select([RefImage::ATTR_ID])
			->column();

		$this->stdout('найдено ' . count($result) . PHP_EOL);

		foreach ($imagesIds as $imageId) {
			$this->stdout('Удаляем изображение id = ' . $imageId . '... ');

			if (Yii::$app->moduleManager->modules->image->deleteImageById($imageId)) {
				$this->stdout('успешно');
			}
			else {
				$this->stdout('ОШИБКА');

				return;
			}

			$this->stdout(PHP_EOL);
		}

		$this->stdout('Удаление успешно выполнено' . PHP_EOL, Console::FG_GREEN);
	}

	/**
	 * Получение путей расположения миграций.
	 *
	 * @return string[]
	 */
	protected function getMigrationsPath() {
		return [
			dirname(__FILE__) . '/../../migrations' => 'yii_image_tables'
		];
	}

}