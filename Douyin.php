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

		/*
		 * 授权用户唯一标识
		 *
		 * @var string
		 *
		 * */
		public $open_id;

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
					if ($result['expires_in'] < $time) {
						$result = $this->RefreshAccessToken();
					}
				}

				$this->SetAccessToken($result);
				$this->SetRefreshToken($result);
				$this->SetOpenId($result);
			}

			return $this->access_token;
		}

		protected function RefreshAccessToken ($type = HttpUtils::DOYIN_TYPE, $force = false)
		{
			if (!Utils::notEmptyStr($this->client_key)) {
				throw new \ParameterError("invalid client_key");
			}

			$time = time();
			if (Utils::notEmptyStr($this->code)) {
				$this->_HttpCall(self::GET_ACCESS_TOKEN, 'POST', ['client_key' => $this->client_key, 'code' => $this->code, 'grant_type' => 'authorization_code', 'client_secret' => $this->client_secret]);
			} elseif (Utils::notEmptyStr($this->refresh_token)) {
				$this->_HttpCall(self::REFRESH_TOKEN, 'POST', ['client_key' => $this->client_key, 'grant_type' => 'refresh_token', 'refresh_token' => $this->refresh_token]);
			} else {
				throw new \ParameterError("The required parameters to obtain the token are missing");
			}

			$accessToken                       = $this->repJson['data'];
			$accessToken['expires']            = $time + $accessToken["expires_in"];
			$accessToken['refresh_expires_in'] = $time + $accessToken["refresh_expires_in"];
			$this->setCache('access_token', $accessToken, $accessToken['expires_in']);

			return $accessToken;
		}

		protected function RenewRefreshToken ($type = HttpUtils::DOYIN_TYPE, $force = false)
		{
			if (!Utils::notEmptyStr($this->client_key)) {
				throw new \ParameterError("invalid client_key");
			}

			$this->code = '';
			$time       = time();
			$this->_HttpCall(self::RENEW_REFRESH_TOKEN, 'POST', ['client_key' => $this->client_key, 'refresh_token' => $this->refresh_token]);
			$refreshToken                       = $this->repJson['data'];
			$refreshToken['refresh_expires_in'] = $time + $refreshToken["expires_in"];
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
			} elseif (!isset($accessToken['expires'])) {
				throw new InvalidParamException('The douyin api expire time must be set.');
			}
			$this->access_token        = $accessToken['access_token'];
			$this->access_token_expire = $accessToken['expires'];
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
			} elseif (!isset($refreshToken['refresh_expires_in'])) {
				throw new InvalidParamException('Douyin refresh_token expire time must be set.');
			}

			$this->refresh_token        = $refreshToken['refresh_token'];
			$this->refresh_token_expire = $refreshToken['refresh_expires_in'];
		}

		/**
		 * 设置 openId
		 *
		 * @param array $openId
		 *
		 * @throws InvalidParamException
		 */
		public function SetOpenId (array $openId)
		{
			if (!isset($openId['open_id'])) {
				throw new InvalidParamException('The douyin api open_id must be set.');
			}
			$this->open_id = $openId['open_id'];
		}

		public static function getAuthorizationUrl ()
		{
			return HttpUtils::MakeUrl(self::DOUYIN_GET_CODE);
		}

		public function userinfo ()
		{
			$this->_HttpCall(self::USERINFO, 'GET');

			return $this->repJson;
		}

		public function fansList ($cursor, $count)
		{
			$this->_HttpCall(self::FANS_LIST, 'GET', ['cursor' => $cursor, 'count' => $count]);

			return $this->repJson;
		}

		public function fansCheck ($openId)
		{
			$this->_HttpCall(self::FANS_CHECK, 'GET', ['follower_open_id' => $openId]);

			return $this->repJson;
		}

		public function followingList ($cursor, $count)
		{
			$this->_HttpCall(self::FOLLOWING_LIST, 'GET', ['cursor' => $cursor, 'count' => $count]);

			return $this->repJson;
		}

		/**
		 * 获取意向用户列表
		 */
		public function getIntentionUser ($cursor, $count, $start_time, $end_time, $action_type)
		{
			Utils::checkIsUInt($cursor, 'cursor');
			Utils::checkIsUInt($count, 'count');
			Utils::checkIsUInt($start_time, 'start_time');
			Utils::checkIsUInt($end_time, 'end_time');
			$args = [
				'cursor'      => $cursor,
				'count'       => $count,
				'start_time'  => $start_time,
				'end_time'    => $end_time,
				'action_type' => $action_type,
			];
			$this->_HttpCall(self::INTENTION_USER, 'GET', $args);

			return $this->repJson;
		}

		/**
		 * 获取意向用户详情
		 */
		public function getIntentionUserInfo ($user_id)
		{
			Utils::checkNotEmptyStr($user_id, 'user_id');
			$args = [
				'user_id' => $user_id
			];
			$this->_HttpCall(self::INTENTION_USER_INFO, 'GET', $args);

			return $this->repJson;
		}
	}
