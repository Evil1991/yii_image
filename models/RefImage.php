<?php

namespace yiiImage\models;

use Yii;
use yii\validators\DefaultValueValidator;
use yii\validators\StringValidator;
use yiiCustom\base\ActiveRecord;
use yiiCustom\behaviors\TimestampUTCBehavior;
use yiiCustom\behaviors\UserBehavior;

/**
 * Справочник изображений.
 *
 * @property int    $id                         Уникальный идентификатор изображения
 * @property string $title                      Название изображения
 * @property string $hash                       Хэш-сумма изображения
 * @property string $created_stamp              Дата-время создания
 * @property string $updated_stamp              Дата-время редактирования
 * @property int    $created_user_id            Ссылка на пользователя, создавшего запись
 * @property int    $updated_user_id            Ссылка на пользователя, отредактировавшего запись
 */
class RefImage extends ActiveRecord {

	const ATTR_ID              = 'id';
	const ATTR_TITLE           = 'title';
	const ATTR_HASH            = 'hash';
	const ATTR_CREATED_STAMP   = 'created_stamp';
	const ATTR_UPDATED_STAMP   = 'updated_stamp';
	const ATTR_CREATED_USER_ID = 'created_user_id';
	const ATTR_UPDATED_USER_ID = 'updated_user_id';

	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [
			[
				'class'                                         => TimestampUTCBehavior::class,
				TimestampUTCBehavior::ATTR_CREATED_AT_ATTRIBUTE => static::ATTR_CREATED_STAMP,
				TimestampUTCBehavior::ATTR_UPDATED_AT_ATTRIBUTE => static::ATTR_UPDATED_STAMP,
			],
			[
				'class'                                 => UserBehavior::class,
				UserBehavior::ATTR_CREATED_AT_ATTRIBUTE => static::ATTR_CREATED_USER_ID,
				UserBehavior::ATTR_UPDATED_AT_ATTRIBUTE => static::ATTR_UPDATED_USER_ID,
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[static::ATTR_TITLE, DefaultValueValidator::class, 'value' => ''],
			[static::ATTR_TITLE, StringValidator::class, 'min' => 5, 'max' => 255],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function afterDelete() {
		parent::afterDelete();

		Yii::$app->moduleManager->modules->image->imageManager->deleteThumbsCache($this->id);
		Yii::$app->moduleManager->modules->image->imageManager->deleteOriginalImage($this->id);
	}

	/**
	 * @inheritdoc
	 */
	public function afterSave($insert, $changedAttributes) {
		parent::afterSave($insert, $changedAttributes);

		Yii::$app->moduleManager->modules->image->imageManager->deleteThumbsCache($this->id);
	}

}