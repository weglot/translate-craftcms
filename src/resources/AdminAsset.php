<?php

namespace weglot\craftweglot\resources;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class AdminAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = __DIR__;

        $this->depends = [
            CpAsset::class,
        ];

        if (\defined('YII_DEBUG')) {
            $this->publishOptions = ['forceCopy' => true];
        }

        $this->css = [
            'vendor/selectize/selectize.css',
            'vendor/selectize/selectize.default.css',
            'css/admin.css',
        ];

        $this->js = [
            'vendor/selectize/selectize.min.js',
            'js/admin.js',
        ];

        parent::init();
    }
}
