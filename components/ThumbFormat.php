<?php

namespace yiiImage\components;
use Yii;
use yii\base\BaseObject;

/**
 * Класс-обёрта для формата изображения.
 * Нужен для внутреннего обращения в модуле.
 */
class ThumbFormat extends BaseObject {

	/**
	 * Получение объекта формата по идентификатору.
	 *
	 * @param string $formatId Идентификатор формата
	 *
	 * @return static|null Объект формата или null, если формат неверный
	 */
	public static function getInstance($formatId) {
		if (!isset(Yii::$app->moduleManager->modules->image->thumbsFormats[$formatId])) {
			return null;
		}

		$format = new static(Yii::$app->moduleManager->modules->image->thumbsFormats[$formatId]);

		$format->id = $formatId;

		return $format;
	}

	/** @var string Идентификатор формата */
	public $id;

	/** @var int Ширина */
	public $width;

	/** @var int Высота */
	public $height;

	/** @var bool Нужно ли накладывать водяной знак */
	public $needWatermark = false;

	/** @var bool Нужно ли обрезать изображение */
	public $needCrop = false;

}