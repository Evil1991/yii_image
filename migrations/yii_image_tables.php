<?php

use yii\db\Migration;
use yii\db\Schema;

/**
 * Handles the creation for table `ref_image`.
 */
class yii_image_tables extends Migration {

	/**
	 * @inheritdoc
	 */
	public function up() {
		$this->createTable('ref_image', [
			'id'              => $this->primaryKey() . ' COMMENT "Уникальный идентификатор изображения"',
			'title'           => Schema::TYPE_STRING . ' NOT NULL DEFAULT "" COMMENT "Название изображения"',
			'hash'            => Schema::TYPE_STRING . '(40) NOT NULL DEFAULT "" COMMENT "Хэш-сумма изображения"',
			'created_stamp'   => Schema::TYPE_DATETIME . ' NOT NULL COMMENT "Дата-время создания"',
			'updated_stamp'   => Schema::TYPE_DATETIME . ' NOT NULL COMMENT "Дата-время редактирования"',
			'created_user_id' => Schema::TYPE_INTEGER . ' NULL COMMENT "Ссылка на пользователя, создавшего запись"',
			'updated_user_id' => Schema::TYPE_INTEGER . ' NULL COMMENT "Ссылка на пользователя, отредактировавшего запись"',
		], 'COMMENT "Справочник изображений"');
	}

	/**
	 * @inheritdoc
	 */
	public function down() {
		$this->dropTable('ref_image');
	}
}
