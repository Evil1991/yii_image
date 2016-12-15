<?php

namespace yiiImage;

use yiiImage\components\ImageManager;
use yiiImage\models\RefImage;
use Yii;
use yii\web\Request;
use yiiCustom\base\Module;

/**
 * Модуль изображений.
 * Задачи модуля: загрузка, обработка, хранение, вывод для фронта и пр. изображений.
 *
 * @property-read ImageManager $imageManager Компонент обработки изображений.
 */
class Image extends Module {

	/** Формат иконки 50x50 */
	const FORMAT_ICON_50 = 'icon50';

	/** Формат превью */
	const FORMAT_THUMB = 'thumb';

	/** Формат маленького размера */
	const FORMAT_MIN = 'min';

	/** Формат среднего разера */
	const FORMAT_MEDIUM = 'medium';

	/** Формат полный */
	const FORMAT_FULL = 'full';

	/** @var array Параметры форматов */
	public $thumbsFormats;

	/**
	 * Загрузка изображения из файла.
	 *
	 * @param string $filePath Путь к файлу.
	 * @param string $title    Название
	 * @param int    $imageId  Идентификатор изображения (если изображение нужно переписать под имеющимся
	 *                         идентификатором)
	 *
	 * @return int|null Идентификатор изображения
	 */
	public function loadImageByFile($filePath, $title = '', $imageId = null) {

		if ($imageId) {
			$image = RefImage::findOne($imageId);

			if ($image === null) {
				return false;
			}
		}
		else {
			$image = new RefImage();
		}

		$image->title = $title;
		$image->hash  = $this->getFileHash($filePath);

		if (!$image->save()) {
			return null;
		}

		if (!$this->imageManager->uploadImage($image->id, $filePath)) {
			return null;
		}

		return $image->id;
	}

	/**
	 * Загрузка изображения по его URL.
	 *
	 * @param string $url     URL изображения
	 * @param string $title   Название
	 * @param int    $imageId Идентификатор изображения (если изображение нужно переписать под имеющимся
	 *                        идентификатором)
	 *
	 * @return int|null Идентификатор изображения
	 */
	public function loadImageByUrl($url, $title = '', $imageId = null) {
		$tmpFile = tempnam(sys_get_temp_dir(), 'img');
		file_put_contents($tmpFile, file_get_contents($url));
		$result = $this->loadImageByFile($tmpFile, $title, $imageId);

		unlink($tmpFile);

		return $result;
	}

	/**
	 * Обновление изображения по источнику-URL.
	 * Проверяет изображение на изменения и если оно изменилось, то обновляет его.
	 *
	 * @param string $imageId Идентификатор изображения
	 * @param string $url     URL изображения
	 * @param string $title   Название
	 *
	 * @return bool
	 */
	public function updateImageByUrl($imageId, $url, $title = '') {
		//Загружаем модель
		/** @var RefImage $image */
		$image = RefImage::findOne($imageId);

		//если модель не найдена
		if ($image === null) {
			return false;
		}

		//загружаем новое изображение
		$tmpFile = tempnam(sys_get_temp_dir(), 'img');
		file_put_contents($tmpFile, file_get_contents($url));

		//вычисляем его хэш и сравниваем с хэшем имеющегося изображения
		$newFileHash = $this->getFileHash($tmpFile);
		//если хэша не совпадают, то перезагружаем изображение
		if ($newFileHash !== $image->hash) {
			$this->loadImageByFile($tmpFile, $title, $image->id);
		}

		unlink($tmpFile);

		return true;
	}

	/**
	 * Удаление изображения по его идентификатору.
	 *
	 * @param int $imageId Идентификатор изображения
	 *
	 * @return bool Успешность удаления
	 */
	public function deleteImageById($imageId) {
		$image = RefImage::findOne($imageId);/** @var RefImage $image */

		if ($image !== null) {
			return $image->delete();
		}

		return false;
	}

	/**
	 * Удаление группы изображений по их идентификаторам.
	 *
	 * @param int[] $imagesIds Идентификаторы изображений
	 *
	 * @return bool
	 */
	public function deleteImagesByIds($imagesIds) {
		$result = true;

		$images = RefImage::findAll([RefImage::ATTR_ID => $imagesIds]);/** @var RefImage $images */

		foreach ($images as $image) {
			$result &= $image->delete();
		}

		return $result;
	}

	/**
	 * Получение URL изображения для вывода на фронте.
	 *
	 * @param int         $imageId    Идентификатор изображения
	 * @param string      $formatId   Идентификатор формата @see {static::$thumbsFormats}
	 * @param bool        $blankImage Выводить ли изображение-пустышку, если требуемое изображение недоступно
	 * @param string|null $protocol   Протокол URL (http или https). Если null, то протокол
	 *                                будет определён автоматически по текущему запросуA
	 *
	 * @return string|null
	 */
	public function getFrontImageUrl($imageId, $formatId, $blankImage = true, $protocol = null) {
		if ($protocol === null) {
			$protocol = $this->getCurrentProtocol();
		}

		return $this->imageManager->getFrontImageUrl($imageId, $formatId, $blankImage, $protocol);
	}

	/**
	 * Получение URL пустышки изображения.
	 *
	 * @param int         $formatId Идентификатор формата @see {static::$thumbsFormats}
	 * @param string|null $protocol Протокол URL (http или https). Если null, то протокол
	 *                              будет определён автоматически по текущему запросу
	 * @return string
	 */
	public function getBlankImageUrl($formatId, $protocol = null) {
		if ($protocol === null) {
			$protocol = $this->getCurrentProtocol();
		}

		return $this->imageManager->getStaticImageUrl($this->imageManager->blankImageFile, $formatId, $protocol);
	}


	/**
	 * Получение хэша файла.
	 * 
	 * @param string $filePath Путь к файлу
	 *                         
	 * @return string Вычисленный хэш
	 */
	protected function getFileHash($filePath) {
		return hash_file('md5', $filePath);
	}

	/**
	 * Получение текущего протокола (http или https).
	 *
	 * @return string
	 */
	protected function getCurrentProtocol() {
		if (Yii::$app->request instanceof Request) {
			$protocol = Yii::$app->request->isSecureConnection ? 'https' : 'http';
		}
		else {
			$protocol = 'http';
		}

		return $protocol;
	}

}