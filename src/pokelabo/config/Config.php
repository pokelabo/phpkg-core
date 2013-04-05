<?php
/**
 * 設定ファイル処理基底
 * @package pokefw
 * @copyright Copyright (c) 2011-2012, Pokelabo Inc.
 * @filesource
 */

namespace pokelabo\config;

use ArrayObject;
use pokelabo\exception\ExceptionCode;
use pokelabo\exception\ImplementationException;
use pokelabo\utility\ArrayUtility;

/**
 * 設定ファイル処理基底
 * @package pokefw
 */
class Config extends ArrayObject {
    /**
     * 設定ファイルへのパス
     * @var string
     */
    protected $_config_path;

    /**
     * 設定ファイルへのパスを設定する<br/>
     * digOrRaise()で発行する例外で表示するためだけに使われる
     * @var string
     */
    public function setConfigPath($config_path) {
        $this->_config_path = $config_path;
    }

    /**
     * 設定されている値を取得する
     * 
     * $path へは path.to.setting の形式で取得したい設定値へのパスを設定する。
     * path.to.setting は $this->_config['path']['to']['setting'] を参照する。
     * パスが存在しない場合は null を返す。
     * 
     * @param string $path 設定値へのパス
     * @param mixed $default_value 見つからなかった場合の値
     * @return mixed        設定値
     */
    public function dig($path, $default_value = null) {
        return ArrayUtility::dig($path, $this, $default_value);
    }

    /**
     * 設定されている値を取得する<br/>
     * 設定されていない場合は例外を生成する
     * 
     * $path へは path.to.setting の形式で取得したい設定値へのパスを設定する。
     * path.to.setting は $this->_config['path']['to']['setting'] を参照する。
     * パスが存在しない場合は null を返す。
     * 
     * @param string $path 設定値へのパス
     * @param mixed $default_value 見つからなかった場合の値
     * @return mixed        設定値
     * @throws ImplementationException 設定ファイルに定義されていない場合
     */
    public function digOrRaise($path) {
        $result = ArrayUtility::dig($path, $this, "\x0\xfe\x1"); // 第三引数はマーカ。有り得ない値なら何でもいい
        if ($result !== "\x0\xfe\x1") {
            return $result;
        }

        if (is_string($this->_config_path)) {
            $message = sprintf('設定ファイルに定義されていません。path: "%s", config: "%s"',
                               $path, $this->_config_path);
        } else {
            $message = sprintf('設定ファイルに定義されていません。path: "%s"', $path);
        }

        throw new ImplementationException($message, ExceptionCode::CONFIG_FILE_DATA_ERROR);
    }
}
