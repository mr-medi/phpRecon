<?php

class Url
{

	/**
	 * Ex: http(s)://
	 * @var string
	 */
	public $scheme;

	/**
	 * Ex: www.mypage.com
	 * @var string
	 */
	public $domain;

	/**
	 * Ex: /search
	 * @var string
	 */
	public $path;

	/**
	 * Ex: ?q=foo
	 * @var string
	 */
	public $query;

	/**
	 * Ex: #top
	 * @var string
	 */
	public $fragment;

	private function __construct($url)
	{
		preg_match_all( '/^(([^:\/?#]+):)?(\/\/([^\/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?$/', $url, $m );
		$this->scheme = $m[2][0];
		$this->domain = $m[4][0];
		$this->path = $m[5][0];
		$this->query = $m[7][0];
		$this->fragment = $m[9][0];
	}

	public static function is_absolute($url)
	{
		$pattern = "/^(?:ftp|https?|feed):\/\/(?:(?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*
		(?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@)?(?:
		(?:[a-z0-9\-\.]|%[0-9a-f]{2})+|(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\]))(?::[0-9]+)?(?:[\/|\?]
		(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})*)?$/xi";
		return (bool) preg_match($pattern, $url);
	}

	private function toString()
	{
		$string = '';
		if (!empty($this->scheme))
			$string .= "{$this->scheme}:";

		if (!empty($this->domain))
			$string .= "//{$this->domain}";

		$string .= $this->normalizePath($this->path);

		if (!empty($this->query))
			$string .= "?{$this->query}";

		if (!empty($this->fragment))
			$string .= "#{$this->fragment}";

		return $string;
	}

	private function normalizePath($path)
	{
		if(empty($path))
			return '';

		$normalized = $path;
		$normalized = preg_replace('`//+`', '/', $normalized, -1, $c0);
		$normalized = preg_replace('`^/\\.\\.?/`', '/', $normalized, -1, $c1);
		$normalized = preg_replace('`/\\.(/|$)`', '/', $normalized, -1, $c2);
		$normalized = preg_replace('`/[^/]*?/\\.\\.(/|$)`', '/', $normalized, 1, $c3);
		$count = $c0 + $c1 + $c2 + $c3;

		return ($count > 0) ? $this->normalizePath($normalized) : $normalized;
	}

	/**
	 * Parse an url string
	 */
	public static function parse($url)
	{
		$uri = new Url($url);
		if (empty( $uri->path))
			$uri->path = '/';
		return $uri;
	}

	/**
	 * Join with a relative url
	 */
	public function join($relative)
	{
		$uri = new Url($relative);
		if(empty($uri->scheme) || empty($uri->domain)
			|| !strpos($uri->path, '/') === 0)
		{
			if(empty($uri->path))
			{
				$uri->path = $this->path;
				if (empty($uri->query))
					$uri->query = $this->query;
			}
			else
			{
				$base_path = $this->path;
				$base_path = strpos($base_path,'/')===FALSE ? '' : preg_replace( '/\/[^\/]+$/', '/', $base_path);

				if (empty($base_path) && empty($this->domain))
					$base_path = '/';
				$uri->path = $base_path.$uri->path;
			}
		}

		if (empty($uri->scheme))
		{
			$uri->scheme = $this->scheme;
			if (empty($uri->domain))
				$uri->domain = $this->domain;
		}

		return $uri->toString();
	}
}
