<?php
/**
 * 設定ファイル読み込み
 * @package pokefw
 * @copyright Copyright (c) 2011-2012, Pokelabo Inc.
 * @filesource
 */

namespace pokelabo\config;

use pokelabo\utility\ArrayUtility;
use pokelabo\exception\ExceptionCode;
use pokelabo\exception\ImplementationException;

/**
 * 設定ファイル読み込み
 * @package pokefw
 */
class ConfigLoader {
    /**
     * 予約環境名: 共通
     * @var string
     */
    const KEY_COMMON = 'all';

    /**
     * 対応可能な拡張子
     */
    public static $_known_types = array('yaml', 'json');

    /**
     * 環境名
     * @var string
     */
    protected static $_root_key = self::KEY_COMMON;
    /**
     * 上書き設定
     * @var array array('key1' => 'key2');
     */
    protected static $_override_map = array();
    /**
     * 読み込み済み設定
     * @var array
     */
    protected static $_config_map = array();

    /**
     * ルートキーを設定する
     * @param string $root_key
     */
    public static function setRootKey($root_key) {
        self::$_root_key = $root_key;
    }

    /**
     * 上書き設定を追加する
     * @param string $target_key
     * @param string $search_key
     */
    public static function addOverrideKey($target_key, $search_key) {
        self::$_override_map[$target_key] = $search_key;
    }

    /**
     * 設定ファイルを読み込む。
     * @param string $file_path   設定ファイルのファイルパス
     * @param string $config_type 設定ファイルの記述方式
     * @return array|null 設定ファイルの値を持つ連想配列<br/>
     * ファイルが存在しない場合はnull。<br/>
     * シンタックスエラーがある場合は、ImplementationException例外が発生する。
     * @throws ImplementationException シンタックスエラー
     */
    public static function load($file_path, $config_type = 'auto') {
        if (!file_exists($file_path)) return null;
        if (array_key_exists($file_path, self::$_config_map)) {
            return self::$_config_map[$file_path];
        }

        if ($config_type == 'auto') {
            $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

            switch ($ext) {
            case 'json':
            case 'yaml':
                $config_type = $ext;
                break;
            case 'yml':
                $config_type = 'yaml';
                break;
            default:
                $config_type = constant('CONFIG_TYPE');
                if ($config_type == '') $config_type = 'json';
                break;
            }
        }

        $config = self::parseFromFile($file_path, $config_type);
        if ($config === false) {
            throw new ImplementationException(get_class() . ': failed to load ' . $file_path,
                                       ExceptionCode::CONFIG_FILE_SYNTAX_ERROR);
        }

        self::$_config_map[$file_path] = self::editConfig($config);
        return self::$_config_map[$file_path];
    }

    /**
     * 読み込みキャッシュをクリア
     */
    public static function clearCache() {
        self::$_config_map = array();
    }

    /**
     * 設定ファイルを読み込み、解析する
     * @param string $file_path   設定ファイルのファイルパス
     * @param string $config_type 設定ファイルの記述方式
     * @return array|false 設定ファイルの値を持つ連想配列。<br/>
     * 内容に構文エラーがあった場合、false
     */
    protected static function parseFromFile($file_path, $config_type) {
        switch ($config_type) {
        case 'json':
            $text = file_get_contents($file_path);
            $config = json_decode($text, true);
            return ($config !== null) ? $config : false;
        case 'yaml':
            $config = yaml_parse_file($file_path);
            return ($config !== false) ? $config : false;
        }

        return false;
    }

    /**
     * 設定内容を編集する
     * @param array $config 設定内容
     */
    protected static function editConfig($config) {
        if (!$config) return $config;

        $result = self::extractEnvironmentConfig($config);
        if (is_array($result)) {
            return self::processOverrides($result);
        } else {
            return $result;
        }
    }

    /**
     * 現在の環境用の設定を抽出する
     * @param array $config 設定内容
     */
    protected static function extractEnvironmentConfig($config) {
        if (array_key_exists(self::KEY_COMMON, $config)) {
            $result = $config[self::KEY_COMMON];
            if (!is_array($result)) {
                return $result;
            }
        } else {
            $result = array();
        }

        if (self::$_root_key === self::KEY_COMMON) {
            return $result;
        }

        $override = ArrayUtility::get(self::$_root_key, $config, array());
        if (is_array($override)) {
            return self::mergeConfigRecursive($result, $override);
        } else {
            return $override;
        }
    }

    /**
     * @param array $config
     * @return array configuration settings
     */
    protected static function processOverrides($config) {
        foreach (self::$_override_map as $key => $search_key) {
            if (!array_key_exists($key, $config)) continue;

            if (array_key_exists($search_key, $config[$key])) {
                $config = self::mergeConfigRecursive($config, $config[$key][$search_key]);
            }
            unset($config[$key]);
        }
        return $config;
    }

    /**
     * @param array $base base array to be merge
     * @param array $override array to override $base
     * Merge configuration array.<br/>
     * merge policy:<br/>
     * - It won't reduce elements of on array, except the $base array consists of
     *   numeric keys.<br/>
     */
    protected static function mergeConfigRecursive($base, $override) {
        foreach ($override as $key => $value) {
            if (!array_key_exists($key, $base) || !is_array($value)) {
                $base[$key] = $override[$key];
                continue;
            }

            if (is_array($base[$key])) {
                if (self::consistsOfNumericKeys($base[$key])) {
                    $base[$key] = $value;
                    continue;
                }
            } else {
                $base[$key] = array();
            }
            $base[$key] = self::mergeConfigRecursive($base[$key], $override[$key]);
        }

        return $base;
    }

    protected static function consistsOfNumericKeys($ar) {
        $numeric_keys = array_filter(array_keys($ar), 'is_int');
        return count($ar) === count($numeric_keys);
    }
}
