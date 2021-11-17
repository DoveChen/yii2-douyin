<?php

	namespace dovechen\yii2\douyin\components;

	/**
	 * Class errorCode
	 * @package dovechen\yii2\douyin\components
	 */
	class errorCode
	{
		/**
		 * @var string[]
		 */
		public $errorInfo
			= [
				//通用
				0       => '成功',
				2100004 => '系统繁忙，此时请开发者稍候再试',
				2100005 => '参数不合法',
				2100007 => '无权限操作',
				2100009 => '用户被禁封使用该操作',
				2190001 => 'quota已用完',
				2190004 => '应用未获得该能力, 请去https://open.douyin.com/申请',
				2190015 => '请求参数access_token openid不匹配',
				2190016 => '当前应用已被封禁或下线',

				// Oauth2
				10002   => '参数错误',
				10003   => 'client_key 错误',
				10004   => '应用权限不足，请申请对应的scope权限',
				10005   => '缺少参数',
				10006   => '非法重定向URI, 需要与APP配置中的"授权回调域"一致。',
				10007   => '授权码过期',
				10008   => 'access_token 无效或过期',
				10010   => 'refresh_token 无效或过期',
				10011   => '应用包名与配置不一致',
				10012   => '应用正在审核中，无法进行授权',
				10013   => 'clientkey 或者clientsecret 错误',
				10014   => '授权的clientkey与获取accesstoken时传递的client_key不一致',
				10015   => '应用类型错误，如将APP应用的client_key 用于 PC 应用',
				10017   => '安卓应用签名与配置不一致，请检查签名信息',
				10020   => '更新新refresh_token次数超出限制',
				2190002 => 'access_token无效',
				2190003 => '用户未授权该api',
				2190008 => 'access_token过期,请刷新或重新授权',

				// 视频
				2190005 => '视频文件太大了',
				2190006 => '视频时长不能超过15分钟',
				2190007 => '无效的视频文件id',
				2114005 => '视频投稿功能已封禁，详情见抖音端上【消息-系统通知】',
				2114007 => '视频发布数量超过每日上限',

				// 视频评论
				2111001 => '命中敏感词',
				2111002 => '获取评论失败',
				2111003 => '无效的评论',
				2111004 => '非本视频评论',
				2111005 => '上一个置顶评论正在审核中, 请稍后再试',
				2111006 => '评论失败',
				2111007 => '获取视频失败',

				// 企业号
				2112001 => '非企业号用户',

				// 素材库
				2113001 => '素材不符合要求, 未通过审核',
				2113002 => '您的素材数量已达上限',
			];

		/**
		 * @param string|int $errCode
		 *
		 * @return mixed|null
		 */
		public static function getErrorInfo ($errCode)
		{
			$errorCode = new self();

			return Utils::arrayGet($errorCode->errorInfo, $errCode);
		}
	}
