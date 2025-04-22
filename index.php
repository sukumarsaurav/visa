<?php
// Front controller for the entire site
$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim($request_uri, '/'));

// Handle news article paths
if (count($path_parts) >= 1 && $path_parts[0] === 'immigration-news') {
    if (count($path_parts) >= 2) {
        // This is a detailed news article view
        $slug = $path_parts[1];
        include('news-detail.php');
        exit;
    } else {
        // This is the news listing page
        include('resources/immigration-news.php');
        exit;
    }
}

// For all other pages, include the regular index page content
include('home.php');
?>