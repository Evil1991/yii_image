<?php

namespace yiiImage\components;

use PHPImageWorkshop\Exception\ImageWorkshopBaseException;
use PHPImageWorkshop\ImageWorkshop;
use Yii;
use yii\base\Component;
use yiiCustom\core\ConfigCollector;

/**
 * Компонент обработки изображений.
 */
class ImageManager extends Component {

	/** @var int Максимальная ширина для оригинала */
	public $maxOriginalWidth;

	/** @var int Максимальная высота для оригинала */
	public $maxOriginalHeight;

	/** @var string Путь для хранения изображений на сервере */
	public $storeImagePath;

	/** @var string Путь для хранения статических файлов на сервере */
	public $storeStaticPath;

	/** @var string Каталог для публикации изображений на фронтэнде */
	public $publishDir;

	/** @var string Каталог в URL-е от корня сайта */
	public $frontendDir;

	/** @var string Имя файла для изображения-пустышки (без пути) */
	public $blankImageFile;

	/**
	 * Загрузка изображения.
	 *
	 * @param string $imageId Идентификатор изображения
	 * @param string $sourceFile Путь к файлу-источнику
	 *
	 * @return bool Успешность загрузки
	 */
	public function uploadImage($imageId, $sourceFile) {
		$filename = $this->getSourceFilename($imageId);

		//если файл уже существует, то удаляем его
		if (file_exists($this->storeImagePath . DIRECTORY_SEPARATOR . $filename)) {
			unlink($this->storeImagePath . DIRECTORY_SEPARATOR . $filename);
		}

		try {
			$imageLayer = ImageWorkshop::initFromPath($sourceFile);

			if ($imageLayer->getHeight() > $this->maxOriginalHeight || $imageLayer->getWidth() > $this->maxOriginalWidth) {
				$imageLayer->resizeToFit($this->maxOriginalWidth, $this->maxOriginalHeight, true);
			}

			$imageLayer->save($this->storeImagePath, $filename);
		}
		catch (ImageWorkshopBaseException $e) {
			return false;
		}

		//т.к. ImageResize::save() не возвращает результат сохранения, то приходиться проверять результат по наличию итогового файла
		if (!file_exists($this->storeImagePath . DIRECTORY_SEPARATOR . $filename)) {
			return false;
		}

		return true;

	}

	/**
	 * Проверка и генерация тамба при его отсутствии
	 *
	 * @param string      $imageId Идентификатор изображения
	 * @param ThumbFormat $format  Формат изображения
	 *
	 * @return bool Успешность выполнения
	 */
	protected function touchThumb($imageId, ThumbFormat $format) {
		$thumbFilename = $this->getThumbFilename($imageId, $format);

		$thumbFilePath = $this->publishDir . DIRECTORY_SEPARATOR . $thumbFilename;

		if (file_exists($thumbFilePath)) {
			return true;
		}

		$originalFilePath = $this->storeImagePath . DIRECTORY_SEPARATOR . $imageId . '.jpg';

		if (file_exists($originalFilePath) === false) {
			return false;
		}

		return $this->createThumbByFile($originalFilePath, $this->publishDir, $thumbFilename, $format);
	}

	/**
	 * Создание тамба из файла.
	 *
	 * @param string      $sourceFilePath Путь к файлу-исходнику
	 * @param string      $destPath       Путь, куда нужно сохранить результат
	 * @param string      $destFilename   Имя файла-результата
	 * @param ThumbFormat $format         Формат тамба
	 *
	 * @return bool Успешность создания тамба
	 */
	protected function createThumbByFile($sourceFilePath, $destPath, $destFilename, ThumbFormat $format) {
		try {
			$imageLayer = ImageWorkshop::initFromPath($sourceFilePath);

			if ($format->needCrop) {
				$imageLayer->resizeByNarrowSideInPixel($format->width, true);
				$imageLayer->cropMaximumInPixel(0, 0, 'mm');
			}
			else {
				$imageLayer->resizeToFit($format->width, $format->height, true);
			}

			if ($format->needWatermark) {
				//@TODO Добавить добавление watermark
			}

			$imageLayer->save($destPath, $destFilename, true, null, 100);

		}
		catch (ImageWorkshopBaseException $e) {
			return false;
		}

		//проверяем, существует ли итоговый файл
		if (file_exists($destPath . DIRECTORY_SEPARATOR . $destFilename)) {
			return true;
		}

		return false;
	}

	/**
	 * Получение URL изображения для вывода на фронте.
	 *
	 * @param int    $imageId    Идентификатор изображения
	 * @param string $formatId   Идентификатор формата @see {static::$thumbsFormats}
	 * @param bool   $blankImage Выводить ли изображение-пустышку, если требуемое изображение недоступно
	 * @param string $protocol   Протокол URL
	 *
	 * @return string|null
	 */
	public function getFrontImageUrl($imageId, $formatId, $blankImage = true, $protocol = 'http') {
		$url = $protocol . '://' . Yii::$app->env->getDomainForEntryPoint(ConfigCollector::ENTRY_POINT_FRONTEND);

		if (isset(Yii::$app->request->baseUrl) && Yii::$app->request->baseUrl) {
			$url .= Yii::$app->request->baseUrl . '/';
		}

		$url .= '/' . $this->frontendDir;

		$format = ThumbFormat::getInstance($formatId);

		if ($format === null) {
			return null;
		}

		$thumbFilename = $this->getThumbFilename($imageId, $format);

		$url .= '/' . $thumbFilename;

		if (file_exists($this->publishDir . DIRECTORY_SEPARATOR . $thumbFilename)) {
			return $url;
		}

		$originalFilePath = $this->storeImagePath . DIRECTORY_SEPARATOR . $imageId . '.jpg';

		if (file_exists($originalFilePath) === false) {
			if ($blankImage) {
				return $this->getStaticImageUrl($this->blankImageFile, $formatId);
			}

			return false;
		}

		if ($this->createThumbByFile($originalFilePath, $this->publishDir, $thumbFilename, $format) === false) {
			if ($blankImage) {
				return $this->getStaticImageUrl($this->blankImageFile, $formatId);
			}

			return null;
		}

		return $url;
	}

	/**
	 * Получение URL статического изображения.
	 *
	 * @param string $imageFileName Имя файла без расширения
	 * @param int    $formatId      Формат изображения
	 * @param string $protocol      Протокол URL
	 *
	 * @return string
	 */
	public function getStaticImageUrl($imageFileName, $formatId, $protocol = 'http') {
		$format = ThumbFormat::getInstance($formatId);

		if ($format === null) {
			return null;
		}
		
		$url = Yii::$app->env->getDomainForEntryPoint(ConfigCollector::ENTRY_POINT_FRONTEND);

		if (isset(Yii::$app->request->baseUrl) && Yii::$app->request->baseUrl) {
			$url .= Yii::$app->request->baseUrl . '/';
		}

		$url .= '/' . $this->frontendDir;

		$url .= '/' . $this->getThumbFilename($imageFileName, $format);

		$thumbFilename = $this->getThumbFilename($imageFileName, $format);

		$thumbFilePath = $this->publishDir . DIRECTORY_SEPARATOR . $thumbFilename;

		if (!file_exists($thumbFilePath)) {
			$originalFilePath = $this->storeStaticPath . DIRECTORY_SEPARATOR . $imageFileName . '.jpg';

			if (file_exists($originalFilePath) === false) {
				return false;
			}

			$this->createThumbByFile($originalFilePath, $this->publishDir, $thumbFilename, $format);
		}

		return $protocol . '://' . $url;
	}

	/**
	 * Удаление оригинала изображения.
	 *
	 * @param int $imageId Идентификатор изображения
	 */
	public function deleteOriginalImage($imageId) {
		//удаляем источник изображения
		$sourceFilename = $this->getSourceFilename($imageId);
		if (file_exists($this->storeImagePath . DIRECTORY_SEPARATOR . $sourceFilename)) {
			unlink($this->storeImagePath . DIRECTORY_SEPARATOR . $sourceFilename);
		}
	}

	/**
	 * Удаление кэща превью изображений.
	 * 
	 * @param $imageId
	 */
	public function deleteThumbsCache($imageId) {
		//и удаляем все его превью
		foreach (glob($this->publishDir . DIRECTORY_SEPARATOR . $imageId . '_*') as $path) {
			if (is_writable($path)) {
				unlink($path);
			}
		}
	}

	/**
	 * Получение имени файла-источника изображения.
	 *
	 * @param $imageId
	 * @return string
	 */
	protected function getSourceFilename($imageId) {
		return $imageId . '.jpg';
	}

	/**
	 * Получение имени файла тамба.
	 *
	 * @param string      $imageId Идентификатор изображения
	 * @param ThumbFormat $format  Формат изображения
	 *
	 * @return string
	 */
	protected function getThumbFilename($imageId, ThumbFormat $format) {
		return $imageId . '_' . $format->id . '.jpg';
	}
	
}