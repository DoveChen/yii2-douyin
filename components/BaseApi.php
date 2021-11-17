<?php

	namespace dovechen\yii2\douyin\components;

	use yii\base\Component;

	abstract class BaseApi extends Component
	{
		public $repJson   = NULL;
		public $repRawStr = NULL;

		/* 重新授权码 */
		const RE_AUTHORIZE_CODE = 10020;

		/* 获取access_token */
		const GET_ACCESS_TOKEN = '/oauth/access_token/';
		/* 刷新refresh_token */
		const RENEW_REFRESH_TOKEN = '/oauth/renew_refresh_token/';
		/* 生成client_token */
		const CLIENT_TOKEN = '/oauth/client_token/';
		/* 刷新access_token */
		const REFRESH_TOKEN = '/oauth/refresh_token/';

		/* 获取授权码(code)_静默 */
		const AUTHORIZE_V2 = '/oauth/authorize/v2/';
		/* 获取授权码(code) */
		const DOUYIN_GET_CODE = '/platform/oauth/connect/';
		/* 获取授权码(code) */
		const TOUTIAO_GET_CODE = '/oauth/authorize/';
		/* 获取授权码(code) */
		const XIGUA_GET_CODE = '/oauth/connect';

		/* 获取用户信息 */
		const USERINFO = '/oauth/userinfo/?access_token=ACCESS_TOKEN&open_id=OPEN_ID';
		/* 粉丝列表 */
		const FANS_LIST = '/fans/list/?access_token=ACCESS_TOKEN&open_id=OPEN_ID';
		/* 粉丝判断 */
		const FANS_CHECK = '/fans/check/?access_token=ACCESS_TOKEN&open_id=OPEN_ID';
		/* 关注列表 */
		const FOLLOWING_LIST = '/following/list/?access_token=ACCESS_TOKEN&open_id=OPEN_ID';

		/* 获取意向用户列表 */
		const INTENTION_USER = '/enterprise/leads/user/list/?access_token=ACCESS_TOKEN&open_id=OPEN_ID';
		/* 获取意向用户详情 */
		const INTENTION_USER_INFO = '/enterprise/leads/user/detail/?access_token=ACCESS_TOKEN&open_id=OPEN_ID';

		protected function GetAccessToken ($type = \HttpUtils::DOYIN_TYPE, $force = false)
		{

		}

		protected function RenewRefreshToken ($type = \HttpUtils::DOYIN_TYPE, $force = false)
		{

		}

		protected function GetClientToken ($type = \HttpUtils::DOYIN_TYPE, $force = false)
		{

		}

		protected function RefreshAccessToken ($type = \HttpUtils::DOYIN_TYPE, $force = false)
		{

		}

		/**
		 * 数据缓存基本键值
		 *
		 * @param $name
		 *
		 * @return string
		 */
		abstract protected function getCacheKey ($name);

		/**
		 * 缓存数据
		 *
		 * @param      $name
		 * @param      $value
		 * @param null $duration
		 *
		 * @return bool
		 */
		protected function setCache ($name, $value, $duration = NULL)
		{
			$duration === NULL && $duration = $this->cacheTime;

			return \Yii::$app->cache->set($this->getCacheKey($name), $value, $duration);
		}

		/**
		 * 获取缓存数据
		 *
		 * @param $name
		 *
		 * @return mixed
		 */
		protected function getCache ($name)
		{
			return \Yii::$app->cache->get($this->getCacheKey($name));
		}

		/**
		 * @param string $error
		 * @param int    $type
		 *
		 * @throws \DyApiError
		 * @throws \HttpError
		 * @throws \OauthApiError
		 * @throws \TtApiError
		 * @throws \XgApiError
		 */
		protected function _ThrowError ($error, $type = \HttpUtils::DOYIN_TYPE)
		{
			switch ($type) {
				case \HttpUtils::DOYIN_TYPE:
					throw new \DyApiError($error);
					break;
				case \HttpUtils::TOUTIAO_TYPE:
					throw new \TtApiError($error);
					break;
				case \HttpUtils::XIGUA_TYPE:
					throw new \XgApiError($error);
					break;
				case \HttpUtils::OAUTH_TYPE:
					throw new \OauthApiError($error);
					break;
				default:
					throw new \HttpError($error);
			}
		}

		/**
		 * @param        $url
		 * @param string $method
		 * @param array  $args
		 * @param bool   $refreshTokenWhenExpired
		 * @param false  $isPostFile
		 * @param int    $type
		 *
		 * @throws \DyApiError
		 * @throws \HttpError
		 * @throws \NetWorkError
		 * @throws \OauthApiError
		 * @throws \ParameterError
		 * @throws \TtApiError
		 * @throws \XgApiError
		 */
		protected function _HttpCall ($url, $method = "GET", $args = [], $refreshTokenWhenExpired = true, $isPostFile = false, $type = HttpUtils::DOYIN_TYPE)
		{
			if ('POST' == strtoupper($method)) {
				$url = \HttpUtils::MakeUrl($url, $type);
				$this->_HttpPostParseToJson($url, $args, $refreshTokenWhenExpired, $isPostFile, $type);
				$this->_CheckErrCode($type);
			} else if ('GET' == strtoupper($method)) {
				if (count($args) > 0) {
					foreach ($args as $key => $value) {
						if ($value === NULL)
							continue;
						if (strpos($url, '?')) {
							$url .= ('&' . $key . '=' . $value);
						} else {
							$url .= ('?' . $key . '=' . $value);
						}
					}
				}
				$url = HttpUtils::MakeUrl($url, $type);
				$this->_HttpGetParseToJson($url, $refreshTokenWhenExpired, $type);
				$this->_CheckErrCode($type);
			} else {
				$this->_ThrowError('wrong method.', $type);
			}
		}

		/**
		 * @param      $url
		 * @param bool $refreshTokenWhenExpired
		 * @param int  $type
		 *
		 * @return bool|\http|string|void|null
		 *
		 * @throws \DyApiError
		 * @throws \HttpError
		 * @throws \NetWorkError
		 * @throws \OauthApiError
		 * @throws \TtApiError
		 * @throws \XgApiError
		 */
		protected function _HttpGetParseToJson ($url, $refreshTokenWhenExpired = true, $type = \HttpUtils::DOYIN_TYPE)
		{
			$retryCnt        = 0;
			$this->repJson   = NULL;
			$this->repRawStr = NULL;

			while ($retryCnt < 2) {
				$tokenType = NULL;
				$realUrl   = $url;

				if (strpos($url, "OPEN_ID")) {
					$realUrl = str_replace('OPEN_ID', $this->open_id, $realUrl);
				}
				if (strpos($url, "ACCESS_TOKEN")) {
					$token     = $this->GetAccessToken($type);
					$realUrl   = str_replace('ACCESS_TOKEN', $token, $realUrl);
					$tokenType = "ACCESS_TOKEN";
				} else {
					$tokenType = "NO_TOKEN";
				}

				$this->repRawStr = HttpUtils::httpGet($realUrl);

				if (!Utils::notEmptyStr($this->repRawStr))
					$this->_ThrowError("empty response", $type);

				$this->repJson = json_decode($this->repRawStr, true);

				$errCode = Utils::arrayGet($this->repJson['data'], "error_code");
				if ($errCode === NULL) {
					$errCode = Utils::arrayGet($this->repJson['extra'], "error_code");
				}

				if ($errCode == 10020) {
					$this->_ThrowError(self::RE_AUTHORIZE_CODE);
				}
				if ($errCode == 10010) {
					if (strpos($url, 'renew_refresh_token')) {
						$this->_ThrowError(self::RE_AUTHORIZE_CODE);
					}
					$result = $this->RenewRefreshToken($type);
					$this->SetRefreshToken($result);
					continue;
				}

				if ($errCode == 2190015 || $errCode == 10008 || $errCode == 2190008) { // token expired
					if ("NO_TOKEN" != $tokenType && true == $refreshTokenWhenExpired) {
						$result = $this->RefreshAccessToken($type);
						$this->SetAccessToken($result);
						$retryCnt += 1;
						continue;
					}
				}

				return $this->repRawStr;
			}
		}

		/**
		 * @param       $url
		 * @param       $args
		 * @param bool  $refreshTokenWhenExpired
		 * @param false $isPostFile
		 * @param int   $type
		 *
		 * @return bool|\http|string|void|null
		 *
		 * @throws \DyApiError
		 * @throws \HttpError
		 * @throws \NetWorkError
		 * @throws \OauthApiError
		 * @throws \TtApiError
		 * @throws \XgApiError
		 */
		protected function _HttpPostParseToJson ($url, $args, $refreshTokenWhenExpired = true, $isPostFile = false, $type = \HttpUtils::DOYIN_TYPE)
		{
			$postData = $args;
			if (!$isPostFile) {
				if (!is_string($args)) {
					//$postData = HttpUtils::Array2Json($args);
				}
			}
			$this->repJson   = NULL;
			$this->repRawStr = NULL;

			$retryCnt = 0;
			while ($retryCnt < 2) {
				$tokenType = NULL;
				$realUrl   = $url;

				if (strpos($url, "OPEN_ID")) {
					$realUrl = str_replace('OPEN_ID', $this->open_id, $realUrl);
				}
				if (strpos($url, "ACCESS_TOKEN")) {
					$token     = $this->GetAccessToken($type);
					$realUrl   = str_replace('ACCESS_TOKEN', $token, $realUrl);
					$tokenType = "ACCESS_TOKEN";
				} else {
					$tokenType = "NO_TOKEN";
				}

				if (strpos($url, "/refresh_token/") !== false) {
					$postData['refresh_token'] = $this->refresh_token;
				}

				$this->repRawStr = HttpUtils::httpPost($realUrl, $postData);

				if (!Utils::notEmptyStr($this->repRawStr))
					$this->_ThrowError("empty response", $type);

				$this->repJson = json_decode($this->repRawStr, true);

				$errCode = Utils::arrayGet($this->repJson['data'], "error_code");

				if ($errCode === NULL) {
					$errCode = Utils::arrayGet($this->repJson['extra'], "error_code");
				}

				if ($errCode == 10020) {
					$this->_ThrowError(self::RE_AUTHORIZE_CODE);
				}
				if ($errCode == 10010) {
					if (strpos($url, '/renew_refresh_token/')) {
						$this->_ThrowError(self::RE_AUTHORIZE_CODE);
					}
					$result = $this->RenewRefreshToken($type);
					$this->SetRefreshToken($result);
					continue;
				}

				if ($errCode == 2190015 || $errCode == 10008 || $errCode == 2190008) { // token expired
					if ("NO_TOKEN" != $tokenType && true == $refreshTokenWhenExpired) {
						$result = $this->RefreshAccessToken($type);
						$this->SetAccessToken($result);
						$retryCnt += 1;
						continue;
					}
				}

				return $this->repRawStr;
			}
		}

		/**
		 * @param int $type
		 *
		 * @throws \DyApiError
		 * @throws \HttpError
		 * @throws \OauthApiError
		 * @throws \ParameterError
		 * @throws \TtApiError
		 * @throws \XgApiError
		 */
		protected function _CheckErrCode ($type = \HttpUtils::DOYIN_TYPE)
		{
			$rsp = $this->repJson;
			$raw = $this->repRawStr;
			if (is_null($rsp))
				return;

			if (!is_array($rsp))
				throw new \ParameterError("invalid type " . gettype($rsp));

			$errCode = Utils::arrayGet($rsp['data'], "error_code");
			if ($errCode === NULL) {
				$errCode = Utils::arrayGet($rsp['extra'], "error_code");
			}
			$errInfo = errorCode::getErrorInfo($errCode);

			if (!is_null($errInfo)) {
				$raw           = json_decode($raw, true);
				$raw['errmsg'] = $errInfo;
				$raw           = json_encode($raw, JSON_UNESCAPED_UNICODE);
			}
			if (!is_int($errCode))
				$this->_ThrowError("invalid errcode type " . gettype($errCode) . ":" . $raw, $type);
			if ($errCode != 0)
				$this->_ThrowError("response error:" . $raw, $type);
		}
	}
