<?php
use yiiImage\components\ImageManager;
use yiiImage\Image;

return [
	'modules' => [
		'image' => [
			'class'         => Image::class,
			'thumbsFormats' => [
				Image::FORMAT_ICON_50 => [
					'width'    => 50,
					'height'   => 50,
				],
				Image::FORMAT_THUMB  => [
					'width'    => 90,
					'height'   => 90,
					'needCrop' => true,
				],
				Image::FORMAT_MIN  => [
					'width'    => 100,
					'height'   => 100,
				],
				Image::FORMAT_MEDIUM => [
					'width'         => 300,
					'height'        => 300,
					'needWatermark' => true,
				],
				Image::FORMAT_FULL   => [
					'width'         => 1000,
					'height'        => 1000,
					'needWatermark' => true,
				],
			],
			'components'    => [
				'imageManager' => [
					'class'             => ImageManager::class,
					'maxOriginalWidth'  => 1000,
					'maxOriginalHeight' => 1000,
					'storeImagePath'    => dirname(__FILE__) . '/../../../../files/images',
					'storeStaticPath'   => dirname(__FILE__) . '/../../../../files/static',
					'publishDir'        => dirname(__FILE__) . '/../../../../frontend/web/files/images',
					'blankImageFile'    => 'image_blank',
					'frontendDir'       => 'files/images',
				],
			],
		],
	],
];