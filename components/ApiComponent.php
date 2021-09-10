<?php

	namespace dovechen\yii2\douyin\components;

	use yii\base\Object;

	class ApiComponent extends Object
	{
		/**
		 * @var BaseApi $api
		 */
		protected $api;

		/**
		 * ApiComponent constructor.
		 *
		 * @param BaseApi $api
		 * @param array   $config
		 */
		public function __construct (BaseApi $api, $config = [])
		{
			/** @var BaseApi api */
			$this->api = $api;

			parent::__construct($config);
		}
	}