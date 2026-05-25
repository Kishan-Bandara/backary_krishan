<?php

function getAdminBaseUrl()
{
	$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/')) : '';
	$projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));

	if ($documentRoot && $projectRoot && stripos($projectRoot, $documentRoot) === 0) {
		$relativePath = substr($projectRoot, strlen($documentRoot));
		return 'http://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\', '/', $relativePath), '/');
	}

	return 'http://' . $_SERVER['HTTP_HOST'] . '/backary_krishan';
}

function redirect($url, $permanent = false)
{
	if ($permanent) {
		header('HTTP/1.1 301 Moved Permanently');
	}

	if (preg_match('#^https?://#i', $url)) {
		header('Location: ' . $url);
		exit();
	}

	$target = rtrim(getAdminBaseUrl(), '/') . '/admin/' . ltrim($url, '/');
	header('Location: ' . $target);
	exit();
}

?>



