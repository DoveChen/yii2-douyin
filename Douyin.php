<?php

	namespace dovechen\yii2\douyin;

	use dovechen\yii2\douyin\components\BaseApi;
	use dovechen\yii2\douyin\components\HttpUtils;
	use dovechen\yii2\douyin\components\Utils;
	use yii\base\InvalidParamException;

	require_once "components/errorInc/error.inc.php";

	class Douyin extends BaseApi
	{
		/**
		 * 应用唯一标识
		 *
		 * @var string
		 */
		public $client_key;

		/**
		 * 应用唯一标识对应的密钥
		 *
		 * @var string
		 */
		public $client_secret;

		/**
		 * 授权码
		 *
		 * @var string
		 */
		public $code;

		/**
		 * 接口调用凭证
		 *
		 * @var string
		 */
		public $access_token;
		/**
		 * 凭证的有效时间（秒）
		 *
		 * @var string
		 */
		public $access_token_expire;

		/**
		 * 刷新令牌，用来刷新 access_token
		 *
		 * @var string
		 */
		public $refresh_token;

		/**
		 * 刷新令牌的有效时间（秒）
		 *
		 * @var string
		 */
		public $refresh_token_expire;

		/**
		 * 数据缓存前缀
		 *
		 * @var string
		 */
		protected $cachePrefix = 'cache_douyin';

		/**
		 * @throws \ParameterError
		 */
		public function init ()
		{
			Utils::checkNotEmptyStr($this->client_key, 'client_key');
			Utils::checkNotEmptyStr($this->client_secret, 'client_secret');
		}

		/**
		 * 获取缓存键值
		 *
		 * @param $name
		 *
		 * @return string
		 */
		protected function getCacheKey ($name)
		{
			return $this->cachePrefix . '_' . $this->client_key . '_' . $name;
		}

		public function GetAccessToken ($type = HttpUtils::DOYIN_TYPE, $force = false)
		{
			$time = time();
			if (!Utils::notEmptyStr($this->access_token) || $this->access_token_expire < $time || $force) {
				$result = !Utils::notEmptyStr($this->access_token) && !$force ? $this->getCache("access_token", false) : false;
				if ($result === false) {
					$result = $this->RefreshAccessToken();
				} else {
					if ($result['expire'] < $time) {
						$result = $this->RefreshAccessToken();
					}
				}

				$this->SetAccessToken($result);
				$this->SetRefreshToken($result);
			}

			return $this->access_token;
		}

		protected function RefreshAccessToken ()
		{
			if (!Utils::notEmptyStr($this->client_key)) {
				throw new \ParameterError("invalid client_key");
			}

			$time = time();
			if (Utils::notEmptyStr($this->code)) {
				$this->_HttpCall(self::GET_ACCESS_TOKEN, 'POST', ['client_key' => $this->client_key, 'code' => $this->code, 'grant_type' => 'authorization_code', 'client_secret' => $this->client_secret]);
			} elseif (Utils::notEmptyStr($this->refresh_token)) {
				$this->_HttpCall(self::REFRESH_TOKEN, 'POST', ['client_key' => $this->client_key, 'grant_type' => 'refresh_token', 'refresh_token' => $this->refresh_token]);
			}

			$accessToken                   = $this->repJson['data'];
			$accessToken['expire']         = $time + $accessToken["expires_in"];
			$accessToken['refresh_expire'] = $time + $accessToken["refresh_expires_in"];
			$this->setCache('access_token', $accessToken, $accessToken['expires_in']);

			return $accessToken;
		}

		protected function RenewRefreshToken ($type = \HttpUtils::DOYIN_TYPE, $force = false)
		{
			if (!Utils::notEmptyStr($this->client_key)) {
				throw new \ParameterError("invalid client_key");
			}

			$this->code = '';
			$time       = time();
			$this->_HttpCall(self::RENEW_REFRESH_TOKEN, 'POST', ['client_key' => $this->client_key, 'refresh_token' => $this->refresh_token]);

			$refreshToken                   = $this->repJson['data'];
			$refreshToken['refresh_expire'] = $time + $refreshToken["expires_in"];
			$this->setCache('refresh_token', $refreshToken, $refreshToken['expires_in']);

			return $refreshToken;
		}

		/**
		 * 设置 accesstoken
		 *
		 * @param array $accessToken
		 *
		 * @throws InvalidParamException
		 */
		public function SetAccessToken (array $accessToken)
		{
			if (!isset($accessToken['access_token'])) {
				throw new InvalidParamException('The douyin api access_token must be set.');
			} elseif (!isset($accessToken['expire'])) {
				throw new InvalidParamException('Douyin access_token expire time must be set.');
			}
			$this->access_token        = $accessToken['access_token'];
			$this->access_token_expire = $accessToken['expire'];
		}

		/**
		 * 设置 refreshToken
		 *
		 * @param array $refreshToken
		 *
		 * @throws InvalidParamException
		 */
		public function SetRefreshToken (array $refreshToken)
		{
			if (!isset($refreshToken['refresh_token'])) {
				throw new InvalidParamException('The douyin api refresh_token must be set.');
			} elseif (!isset($refreshToken['refresh_expire'])) {
				throw new InvalidParamException('Douyin refresh_token expire time must be set.');
			}
			$this->refresh_token        = $refreshToken['refresh_token'];
			$this->refresh_token_expire = $refreshToken['refresh_expire'];
		}

	}