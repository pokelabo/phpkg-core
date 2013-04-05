<?php
/**
 * 設定ファイルリポジトリ
 * @package pokefw
 * @copyright Copyright (c) 2011-2012, Pokelabo Inc.
 * @filesource
 */

namespace pokelabo\config;

use pokelabo\utility\ArrayUtility;

/**
 * 設定ファイルリポジトリ
 * @package pokefw
 */
class ConfigRepository {
    /**
     * 設定ディレクトリ
     * @var string
     */
    protected static $_config_dirs = array();
    /**
     * 設定種別 "yaml" | "json" | "auto"
     * @var string
     */
    protected static $_config_type = 'auto';
    /**
     * 設定マップ
     * @var array
     */
    protected static $_config_map;

    /**
     * 設定ディレクトリを設定
     */
    public static function addConfigDir($config_dir) {
        static::$_config_dirs[] = $config_dir;
    }

    /**
     * 設定種別を設定
     */
    public static function setConfigType($config_type) {
        static::$_config_type = $config_type;
    }

    /**
     * 設定取得
     * @param string $config_name 設定名
     * @param string|null $root_path 構造ルートへのパス(ピリオド区切り)
     * @return AbstractConfig 設定インスタンス
     */
    public static function load($config_name, $root_path = null) {
        $config_key = static::generateConfigKey($config_name, $root_path);
        if (isset(static::$_config_map[$config_key])) {
            return static::$_config_map[$config_key];
        }

        $config_path = static::findConfig($config_name);
        if ($config_path !== false) {
            $config = ConfigLoader::load($config_path, 'auto');
            if ($config && isset($root_path)) {
                $config = ArrayUtility::dig($root_path, $config);
            }
        } else {
            $config = null;
        }

        if (is_array($config)) {
            $instance = new Config($config);
            $instance->setConfigPath($config_path);
        } else {
            $instance = false;
        }
        static::$_config_map[$config_key] = $instance;

        return $instance;
    }

    protected static function findConfig($config_name) {
        $file_names = array();
        $ext = strtolower(pathinfo($config_name, PATHINFO_EXTENSION));
        if (in_array($ext, ConfigLoader::$_known_types)) {
            $file_names[] = $config_name;
        } else if (in_array(static::$_config_type, ConfigLoader::$_known_types)) {
            $file_names[] = $config_name . '.' . static::$_config_type;
        } else {
            foreach (ConfigLoader::$_known_types as $config_type) {
                $file_names[] = $config_name . '.' . $config_type;
            }
        }

        foreach (static::$_config_dirs as $config_dir) {
            foreach ($file_names as $file_name) {
                $config_path = $config_dir . DIRECTORY_SEPARATOR . $file_name;
                if (file_exists($config_path)) {
                    return $config_path;
                }
            }
        }

        return false;
    }

    /**
     * 設定キー生成
     * @param string $config_name 設定名
     * @param string|null $root_path 構造ルートへのパス(ピリオド区切り)
     * @return string
     */
    protected static function generateConfigKey($config_name, $root_path) {
        return isset($root_path) ? ($config_name . '|' . $root_path) : $config_name;
    }
}
